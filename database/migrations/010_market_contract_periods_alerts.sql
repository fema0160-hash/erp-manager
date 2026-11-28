-- ERP Manager — migration 010 : périodes contractuelles et alertes
-- Référence : erp-manager/database/migrations/010_market_contract_periods_alerts.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 009 installées et contrôlées ;
--   - market_lots, market_guarantees et stored_documents disponibles ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence des tables et contraintes.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
--
-- Les alertes sont des occurrences enregistrées en base. Leur envoi réel
-- nécessite un traitement planifié (cron/worker) qui n'est pas créé ici.

SET NAMES utf8mb4;

-- 1. Périodes initiales et renouvellements propres à chaque lot.
CREATE TABLE market_contract_periods (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    period_number SMALLINT UNSIGNED NOT NULL,
    period_type VARCHAR(12) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_months SMALLINT UNSIGNED NOT NULL,
    decision_date DATE NULL,
    period_status VARCHAR(16) NOT NULL DEFAULT 'prevue',
    notes TEXT NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_contract_periods_lot_number (lot_id, period_number),
    UNIQUE KEY uq_market_contract_periods_id_market_company (id, market_id, company_id),
    UNIQUE KEY uq_market_contract_periods_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_contract_periods_lot_dates (lot_id, start_date, end_date),
    KEY idx_market_contract_periods_company_status (company_id, period_status, end_date),
    KEY idx_market_contract_periods_document (document_id, market_id, company_id),
    KEY idx_market_contract_periods_created_by (created_by),
    CONSTRAINT fk_market_contract_periods_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_contract_periods_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_contract_periods_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_contract_periods_number CHECK (period_number > 0),
    CONSTRAINT chk_market_contract_periods_type
        CHECK (period_type IN ('initiale', 'renouvellement')),
    CONSTRAINT chk_market_contract_periods_dates
        CHECK (end_date >= start_date AND duration_months > 0),
    CONSTRAINT chk_market_contract_periods_status
        CHECK (period_status IN ('prevue', 'active', 'terminee', 'annulee')),
    CONSTRAINT chk_market_contract_periods_decision
        CHECK (period_type <> 'renouvellement' OR decision_date IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Règles d'alerte configurables par société.
CREATE TABLE market_alert_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    alert_type VARCHAR(30) NOT NULL,
    days_before SMALLINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    recipient_user_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_alert_rules_company_type_days
        (company_id, alert_type, days_before),
    UNIQUE KEY uq_market_alert_rules_id_company (id, company_id),
    KEY idx_market_alert_rules_recipient (recipient_user_id),
    KEY idx_market_alert_rules_created_by (created_by),
    CONSTRAINT fk_market_alert_rules_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alert_rules_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alert_rules_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_alert_rules_type
        CHECK (alert_type IN (
            'renouvellement', 'fin_contrat', 'expiration_caution', 'remise_offres'
        )),
    CONSTRAINT chk_market_alert_rules_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Occurrences d'alertes, dédoublonnées par clé déterministe.
CREATE TABLE market_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    market_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    contract_period_id BIGINT UNSIGNED NULL,
    guarantee_id BIGINT UNSIGNED NULL,
    rule_id BIGINT UNSIGNED NOT NULL,
    recipient_user_id INT UNSIGNED NULL,
    deduplication_key CHAR(64) NOT NULL,
    scheduled_for DATETIME NOT NULL,
    alert_status VARCHAR(16) NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    acknowledged_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_alerts_deduplication_key (deduplication_key),
    KEY idx_market_alerts_company_status_schedule (company_id, alert_status, scheduled_for),
    KEY idx_market_alerts_lot_schedule (lot_id, scheduled_for),
    KEY idx_market_alerts_period (contract_period_id, lot_id, market_id, company_id),
    KEY idx_market_alerts_guarantee (guarantee_id, lot_id, market_id, company_id),
    KEY idx_market_alerts_rule (rule_id, company_id),
    KEY idx_market_alerts_recipient (recipient_user_id),
    CONSTRAINT fk_market_alerts_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alerts_period
        FOREIGN KEY (contract_period_id, lot_id, market_id, company_id)
        REFERENCES market_contract_periods(id, lot_id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alerts_guarantee
        FOREIGN KEY (guarantee_id, lot_id, market_id, company_id)
        REFERENCES market_guarantees(id, lot_id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alerts_rule
        FOREIGN KEY (rule_id, company_id)
        REFERENCES market_alert_rules(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_alerts_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_alerts_status
        CHECK (alert_status IN ('pending', 'sent', 'acknowledged', 'cancelled')),
    CONSTRAINT chk_market_alerts_sent_date
        CHECK (alert_status NOT IN ('sent', 'acknowledged') OR sent_at IS NOT NULL),
    CONSTRAINT chk_market_alerts_ack_date
        CHECK (alert_status <> 'acknowledged' OR acknowledged_at IS NOT NULL),
    CONSTRAINT chk_market_alerts_key
        CHECK (deduplication_key REGEXP '^[0-9a-fA-F]{64}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Une seule période initiale par lot ; chaque renouvellement exige la décision
--    et les justificatifs nécessaires.
-- 2. Verrouiller le lot avant insertion/modification d'une période et vérifier
--    l'absence de chevauchement. Une dérogation doit être motivée et auditée.
-- 3. Les périodes annulées restent historisées ; ne pas les supprimer.
-- 4. Créer les occurrences à partir des règles actives et calculer une clé SHA-256
--    stable incluant société, lot, objet, règle et échéance.
-- 5. L'envoi n'est effectué que par un cron/worker futur ; le worker doit
--    verrouiller l'occurrence, contrôler qu'elle est encore pertinente et
--    mettre à jour son état après le résultat de l'envoi.
-- 6. Une règle désactivée n'efface pas les occurrences déjà créées ; celles-ci
--    doivent être réévaluées explicitement par le service.
-- 7. Vérifier que le destinataire a accès à la société et au marché avant
--    d'afficher ou transmettre le contenu de l'alerte.
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW CREATE TABLE market_contract_periods;
-- SHOW CREATE TABLE market_alert_rules;
-- SHOW CREATE TABLE market_alerts;
--
-- Aucune période contractuelle ni alerte existante n'est modifiée par ce script.

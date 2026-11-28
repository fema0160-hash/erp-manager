-- ERP Manager — migration 011 : exécution, situations, retenues et clôture
-- Référence : erp-manager/database/migrations/011_market_execution_retention_closure.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 010 installées et contrôlées ;
--   - market_lots, market_award_details, market_guarantees,
--     market_contract_periods et stored_documents disponibles ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence des tables, index, contraintes et triggers.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
--
-- RETENUE CONFIRMÉE :
--   - 10 % du TTC de chaque situation validée ;
--   - plafond cumulé de 7 % du TTC initial attribué au lot ;
--   - dernier prélèvement limité au solde du plafond ;
--   - taux et plafond sont figés par lot dans market_award_details (006) ;
--   - chaque situation ne peut produire qu'un seul événement de retenue ;
--   - les événements de retenue sont immuables.

SET NAMES utf8mb4;

-- 1. Journal des événements d'exécution par lot.
CREATE TABLE market_execution_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(24) NOT NULL,
    event_date DATETIME NOT NULL,
    progress_percent DECIMAL(5,2) NULL,
    work_amount DECIMAL(15,2) NULL,
    description TEXT NOT NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_execution_events_lot_date (lot_id, event_date),
    KEY idx_market_execution_events_document (document_id, market_id, company_id),
    KEY idx_market_execution_events_user (created_by),
    CONSTRAINT fk_market_execution_events_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_execution_events_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_execution_events_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_execution_events_type
        CHECK (event_type IN (
            'demarrage', 'avancement', 'arret', 'reprise', 'observation', 'visite'
        )),
    CONSTRAINT chk_market_execution_events_progress
        CHECK (progress_percent IS NULL OR progress_percent BETWEEN 0 AND 100),
    CONSTRAINT chk_market_execution_events_amount
        CHECK (work_amount IS NULL OR work_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Situations de travaux par lot.
CREATE TABLE market_work_situations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    situation_number VARCHAR(40) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    amount_ht DECIMAL(15,2) NOT NULL,
    amount_vat DECIMAL(15,2) NOT NULL,
    amount_ttc DECIMAL(15,2) NOT NULL,
    situation_status VARCHAR(20) NOT NULL DEFAULT 'brouillon',
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    approved_by INT UNSIGNED NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_work_situations_lot_number (lot_id, situation_number),
    UNIQUE KEY uq_market_work_situations_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_work_situations_lot_period (lot_id, period_start, period_end),
    KEY idx_market_work_situations_company_status (company_id, situation_status, approved_at),
    KEY idx_market_work_situations_document (document_id, market_id, company_id),
    KEY idx_market_work_situations_created_by (created_by),
    KEY idx_market_work_situations_approved_by (approved_by),
    CONSTRAINT fk_market_work_situations_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_work_situations_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_work_situations_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_work_situations_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_work_situations_period
        CHECK (period_end >= period_start),
    CONSTRAINT chk_market_work_situations_amounts
        CHECK (amount_ht >= 0 AND amount_vat >= 0 AND amount_ttc >= 0),
    CONSTRAINT chk_market_work_situations_status
        CHECK (situation_status IN ('brouillon', 'soumise', 'validee', 'payee', 'rejetee')),
    CONSTRAINT chk_market_work_situations_approval_pair
        CHECK ((approved_at IS NULL AND approved_by IS NULL)
            OR (approved_at IS NOT NULL AND approved_by IS NOT NULL)),
    CONSTRAINT chk_market_work_situations_approved
        CHECK (situation_status NOT IN ('validee', 'payee')
            OR (approved_at IS NOT NULL AND approved_by IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Événements de retenue TTC. Une seule ligne au maximum par situation.
CREATE TABLE market_retention_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    work_situation_id BIGINT UNSIGNED NOT NULL,
    situation_amount_ttc DECIMAL(15,2) NOT NULL,
    retention_rate_percent_snapshot DECIMAL(5,2) NOT NULL,
    calculated_amount_ttc DECIMAL(15,2) NOT NULL,
    cap_amount_ttc_snapshot DECIMAL(15,2) NOT NULL,
    cumulative_before_ttc DECIMAL(15,2) NOT NULL,
    retained_amount_ttc DECIMAL(15,2) NOT NULL,
    cumulative_after_ttc DECIMAL(15,2) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_retention_events_situation (work_situation_id),
    UNIQUE KEY uq_market_retention_events_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_retention_events_lot_date (lot_id, created_at),
    KEY idx_market_retention_events_company (company_id, lot_id),
    CONSTRAINT fk_market_retention_events_situation
        FOREIGN KEY (work_situation_id, lot_id, market_id, company_id)
        REFERENCES market_work_situations(id, lot_id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_retention_events_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_retention_events_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_retention_events_values
        CHECK (
            situation_amount_ttc >= 0
            AND retention_rate_percent_snapshot BETWEEN 0 AND 100
            AND calculated_amount_ttc >= 0
            AND cap_amount_ttc_snapshot >= 0
            AND cumulative_before_ttc >= 0
            AND retained_amount_ttc >= 0
            AND cumulative_after_ttc >= 0
            AND retained_amount_ttc <= calculated_amount_ttc
            AND cumulative_before_ttc <= cap_amount_ttc_snapshot
            AND cumulative_after_ttc <= cap_amount_ttc_snapshot
            AND cumulative_after_ttc = cumulative_before_ttc + retained_amount_ttc
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutabilité SQL de l'historique des prélèvements.
DELIMITER //
CREATE TRIGGER trg_market_retention_events_no_update
BEFORE UPDATE ON market_retention_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Historique retenue immuable : UPDATE interdit';
END//

CREATE TRIGGER trg_market_retention_events_no_delete
BEFORE DELETE ON market_retention_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Historique retenue immuable : DELETE interdit';
END//
DELIMITER ;

-- 4. Avenants par lot.
CREATE TABLE market_amendments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    amendment_number VARCHAR(60) NOT NULL,
    amendment_date DATE NOT NULL,
    reason TEXT NOT NULL,
    amount_delta DECIMAL(15,2) NULL,
    new_end_date DATE NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_amendments_lot_number (lot_id, amendment_number),
    KEY idx_market_amendments_lot_date (lot_id, amendment_date),
    KEY idx_market_amendments_document (document_id, market_id, company_id),
    CONSTRAINT fk_market_amendments_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_amendments_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_amendments_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Réceptions provisoires et définitives par lot.
CREATE TABLE market_receptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    reception_type VARCHAR(16) NOT NULL,
    reception_date DATE NOT NULL,
    reservations TEXT NULL,
    reservations_lifted_at DATE NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_receptions_lot_type_date (lot_id, reception_type, reception_date),
    KEY idx_market_receptions_document (document_id, market_id, company_id),
    CONSTRAINT fk_market_receptions_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_receptions_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_receptions_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_receptions_type
        CHECK (reception_type IN ('provisoire', 'definitive')),
    CONSTRAINT chk_market_receptions_reservations_date
        CHECK (reservations_lifted_at IS NULL OR reservations_lifted_at >= reception_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Clôture unique par lot.
CREATE TABLE market_closures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    tracking_end_date DATE NOT NULL,
    final_settlement_amount DECIMAL(15,2) NULL,
    retention_released_amount DECIMAL(15,2) NULL,
    guarantees_returned TINYINT(1) NOT NULL DEFAULT 0,
    missing_documents_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    exception_reason TEXT NULL,
    closure_notes TEXT NULL,
    closed_by INT UNSIGNED NOT NULL,
    closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_closures_lot (lot_id),
    UNIQUE KEY uq_market_closures_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_closures_company_date (company_id, tracking_end_date),
    KEY idx_market_closures_user (closed_by),
    CONSTRAINT fk_market_closures_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_closures_user
        FOREIGN KEY (closed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_closures_amounts
        CHECK (
            (final_settlement_amount IS NULL OR final_settlement_amount >= 0)
            AND (retention_released_amount IS NULL OR retention_released_amount >= 0)
        ),
    CONSTRAINT chk_market_closures_guarantees CHECK (guarantees_returned IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Une situation ne peut être validée que par un utilisateur autorisé, après
--    contrôle du marché, du lot, de la période et des montants HT/TVA/TTC.
-- 2. Dans une transaction, verrouiller le lot et la situation ; vérifier que
--    la situation est validée et qu'aucun événement de retenue n'existe.
-- 3. Lire les instantanés retention_rate_percent, retention_cap_percent,
--    retention_base_amount_ttc et retention_cap_amount_ttc de
--    market_award_details pour ce lot. Ne jamais relire les paramètres actuels
--    de société pour recalculer un lot déjà attribué.
-- 4. Calcul :
--      théorique = ARRONDI(situation_amount_ttc * taux / 100, 2)
--      solde = MAX(0, plafond_ttc - cumul_précédent)
--      retenue = MIN(théorique, solde)
--      cumul_après = cumul_précédent + retenue
--    Si solde = 0, ne créer aucun événement.
-- 5. La création de l'événement doit être dans la même transaction que le
--    passage de la situation à validée. L'index UNIQUE(work_situation_id)
--    protège contre un double prélèvement concurrent.
-- 6. Les événements de retenue ne sont jamais modifiés/supprimés. Toute
--    correction comptable doit être représentée par un événement correctif
--    dans une extension ultérieure, sans altérer l'événement d'origine.
-- 7. La réception définitive, la restitution des cautions et la clôture du lot
--    doivent être contrôlées par le service métier ; plusieurs réceptions sont
--    permises et restent historisées.
-- 8. La clôture exige une justification si des documents sont manquants ou si
--    des cautions ne sont pas confirmées comme restituées.
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW CREATE TABLE market_execution_events;
-- SHOW CREATE TABLE market_work_situations;
-- SHOW CREATE TABLE market_retention_events;
-- SHOW CREATE TABLE market_amendments;
-- SHOW CREATE TABLE market_receptions;
-- SHOW CREATE TABLE market_closures;
-- SHOW TRIGGERS LIKE 'trg_market_retention_events%';
--
-- Aucune donnée historique des migrations 001 à 010 n'est modifiée par ce script.

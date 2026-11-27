-- ERP Manager — migration 003 : cycle de vie des marchés
-- Référence : erp-manager/database/migrations/003_market_lifecycle.sql
--
-- IMPORTANT :
-- 1) Pré-requis : migrations 001 et 002 déjà installées.
-- 2) Préparer une sauvegarde complète de la base avant exécution.
-- 3) Cette migration est à exécuter UNE SEULE FOIS, après validation.
-- 4) Cibles : MySQL 8.0.16+ ou MariaDB 10.2.1+ (contraintes CHECK appliquées).
--    Version relevée sur l'environnement XAMPP : MariaDB 10.4.32.
-- 5) Cette migration n'est PAS exécutée automatiquement par l'application.
--
-- STRATÉGIE DE COMPATIBILITÉ :
-- Les anciennes colonnes markets.status et markets.phase sont conservées
-- sans modification. Les nouvelles colonnes lifecycle_* deviennent la
-- référence du futur module. Les anciennes valeurs sont copiées dans
-- market_transitions pour audit et rapprochement.
--
-- Les anciens statuts "perdu_annule" ne permettent pas de distinguer
-- PERDU, ANNULE et INFRUCTUEUX : ils sont donc classés "a_qualifier".
-- Aucune décision métier n'est inventée ou perdue.

SET NAMES utf8mb4;

ALTER TABLE markets
    ADD COLUMN lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'en_cours'
        COMMENT 'Statut normalisé du cycle de vie',
    ADD COLUMN lifecycle_phase TINYINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Phase normalisée du cycle de vie (1 à 8)',
    ADD COLUMN decision_date DATE NULL,
    ADD COLUMN decision_reason TEXT NULL,
    ADD COLUMN winner_company_name VARCHAR(180) NULL
        COMMENT 'Entreprise concurrente attributaire si le marché est perdu',
    ADD COLUMN winner_amount DECIMAL(15,2) NULL
        COMMENT 'Montant retenu pour le concurrent attributaire',
    ADD COLUMN decision_observations TEXT NULL,
    ADD COLUMN awarded_amount DECIMAL(15,2) NULL
        COMMENT 'Montant attribué à notre entreprise';

CREATE TABLE market_transitions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(30) NOT NULL
        COMMENT 'migration, phase, decision, reopen ou close',
    from_status VARCHAR(20) NULL,
    from_phase TINYINT UNSIGNED NULL,
    to_status VARCHAR(20) NOT NULL,
    to_phase TINYINT UNSIGNED NOT NULL,
    legacy_status VARCHAR(20) NULL
        COMMENT 'Valeur status avant migration initiale',
    legacy_phase TINYINT UNSIGNED NULL
        COMMENT 'Valeur phase avant migration initiale',
    changed_by INT UNSIGNED NULL
        COMMENT 'NULL uniquement pour les lignes créées par la migration',
    reason TEXT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_transitions_market_date (market_id, changed_at),
    KEY idx_market_transitions_user (changed_by),
    CONSTRAINT fk_market_transitions_market
        FOREIGN KEY (market_id) REFERENCES markets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_transitions_user
        FOREIGN KEY (changed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_transitions_to_phase
        CHECK (to_phase BETWEEN 1 AND 8),
    CONSTRAINT chk_market_transitions_from_phase
        CHECK (from_phase IS NULL OR from_phase BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversion prudente des anciennes valeurs.
-- Ancienne phase 2 (Publication) -> nouvelle phase 3 (Préparation soumission).
-- Ancienne phase 3 (Pour étude) -> nouvelle phase 2 (Étude et BPU).
-- Les phases 4 et 5 gardent leur correspondance fonctionnelle.
-- Une ancienne phase 6 n'est interprétée comme clôturée que si l'ancien
-- statut était déjà "cloture". Les autres cas ambigus sont à qualifier.

UPDATE markets
SET
    lifecycle_status = CASE
        WHEN status IN ('en_attente', 'en_cours') AND phase = 6 THEN 'a_qualifier'
        WHEN status IN ('en_attente', 'en_cours') THEN 'en_cours'
        WHEN status = 'gagne' THEN 'attribue'
        WHEN status = 'cloture' THEN 'cloture'
        WHEN status = 'perdu_annule' THEN 'a_qualifier'
        ELSE 'a_qualifier'
    END,
    lifecycle_phase = CASE
        WHEN status = 'cloture' THEN 8
        WHEN status = 'gagne' THEN 6
        WHEN phase = 1 THEN 1
        WHEN phase = 2 THEN 3
        WHEN phase = 3 THEN 2
        WHEN phase = 4 THEN 4
        WHEN phase = 5 THEN 5
        WHEN phase = 6 THEN 5
        ELSE 1
    END;

ALTER TABLE markets
    ADD CONSTRAINT chk_markets_lifecycle_status
        CHECK (lifecycle_status IN (
            'en_cours', 'attribue', 'perdu', 'annule',
            'infructueux', 'cloture', 'a_qualifier'
        )),
    ADD CONSTRAINT chk_markets_lifecycle_phase
        CHECK (lifecycle_phase BETWEEN 1 AND 8),
    ADD CONSTRAINT chk_markets_decision_data
        CHECK (
            (lifecycle_status <> 'perdu' OR (
                winner_company_name IS NOT NULL AND winner_company_name <> ''
                AND winner_amount IS NOT NULL
                AND decision_observations IS NOT NULL AND decision_observations <> ''
            ))
            AND (lifecycle_status NOT IN ('annule', 'infructueux')
                OR (decision_reason IS NOT NULL AND decision_reason <> ''))
            AND (lifecycle_status <> 'attribue'
                OR (awarded_amount IS NOT NULL AND decision_date IS NOT NULL))
        );

-- Historisation des valeurs initiales et des valeurs proposées.
-- L'utilisateur est NULL car il s'agit d'une opération technique de migration.
INSERT INTO market_transitions (
    market_id, action_type, from_status, from_phase,
    to_status, to_phase, legacy_status, legacy_phase,
    changed_by, reason, changed_at
)
SELECT
    id,
    'migration',
    NULL,
    NULL,
    lifecycle_status,
    lifecycle_phase,
    status,
    phase,
    NULL,
    CASE
        WHEN status = 'perdu_annule'
            THEN 'À qualifier par GEST : l’ancien statut ne distingue pas perdu, annulé et infructueux.'
        WHEN status IN ('en_attente', 'en_cours') AND phase = 6
            THEN 'À qualifier par GEST : ancien statut en cours/en attente associé à l’ancienne phase archivée.'
        WHEN status = 'cloture' AND phase <> 6
            THEN 'Ancien statut clôturé : phase normalisée à 8 ; vérifier la cohérence du dossier.'
        ELSE 'Initialisation du cycle de vie ; valeurs historiques conservées dans legacy_status et legacy_phase.'
    END,
    CURRENT_TIMESTAMP
FROM markets;

-- Contrôle après migration (à exécuter et examiner avant toute mise en service) :
-- SELECT lifecycle_status, lifecycle_phase, COUNT(*) AS total
-- FROM markets GROUP BY lifecycle_status, lifecycle_phase ORDER BY lifecycle_status, lifecycle_phase;
--
-- SELECT m.id, m.reference, t.legacy_status, t.legacy_phase,
--        m.lifecycle_status, m.lifecycle_phase, t.reason
-- FROM markets m
-- INNER JOIN market_transitions t
--   ON t.market_id = m.id AND t.action_type = 'migration'
-- WHERE m.lifecycle_status = 'a_qualifier'
-- ORDER BY m.id;
--
-- Aucune ligne de marché n'est supprimée. Les anciennes colonnes status/phase
-- restent disponibles pour contrôle pendant la période de transition.

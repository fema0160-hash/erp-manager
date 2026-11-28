-- ERP Manager — migration 004 : qualification et validation des résultats
-- Référence : erp-manager/database/migrations/004_result_qualification_validation.sql
--
-- STATUT : BROUILLON À APPROUVER — NE PAS EXÉCUTER AVANT AUTORISATION EXPLICITE.
-- Cette migration est additive : elle conserve les colonnes historiques et les
-- décisions déjà présentes dans markets. Elle ne modifie aucune donnée métier.
--
-- PRÉREQUIS :
--   - migrations 001, 002 et 003 installées ;
--   - sauvegarde complète vérifiée ;
--   - vérifier l'absence préalable des colonnes, tables et contraintes ci-dessous.
--
-- COMPATIBILITÉ VISÉE : MariaDB 10.4.32 / InnoDB / utf8mb4.
-- ATTENTION : les DDL MariaDB provoquent des commits implicites. En cas d'échec
-- partiel, arrêter et examiner le schéma avant toute reprise. Ne pas relancer
-- aveuglément le fichier.
--
-- RÈGLES :
--   - une qualification est une proposition en attente ;
--   - une qualification reçoit une seule décision finale : validée ou refusée ;
--   - une nouvelle proposition après refus crée une nouvelle qualification ;
--   - seul le traitement applicatif d'une validation approuvée met à jour
--     markets.lifecycle_status/lifecycle_phase et les colonnes legacy ;
--   - par défaut, tout utilisateur autorisé sur le marché peut qualifier
--     et valider, y compris la même personne ;
--   - aucune permission ou configuration supplémentaire n'est créée ici.

SET NAMES utf8mb4;

CREATE TABLE market_result_qualifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    result_code VARCHAR(20) NOT NULL
        COMMENT 'attribue, perdu, annule ou infructueux',
    qualification_status VARCHAR(20) NOT NULL DEFAULT 'en_attente'
        COMMENT 'en_attente, validee ou refusee',
    decision_date DATE NULL,
    decision_reason TEXT NULL,
    winner_company_name VARCHAR(180) NULL,
    winner_amount DECIMAL(15,2) NULL,
    decision_observations TEXT NULL,
    awarded_amount DECIMAL(15,2) NULL,
    qualified_by INT UNSIGNED NOT NULL,
    qualified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_result_qualifications_market (market_id, qualified_at),
    KEY idx_market_result_qualifications_status (qualification_status),
    KEY idx_market_result_qualifications_user (qualified_by),
    CONSTRAINT fk_market_result_qualifications_market
        FOREIGN KEY (market_id) REFERENCES markets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_result_qualifications_user
        FOREIGN KEY (qualified_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_result_qualification_code
        CHECK (result_code IN ('attribue', 'perdu', 'annule', 'infructueux')),
    CONSTRAINT chk_market_result_qualification_status
        CHECK (qualification_status IN ('en_attente', 'validee', 'refusee')),
    CONSTRAINT chk_market_result_qualification_data
        CHECK (
            (result_code <> 'attribue' OR
                (decision_date IS NOT NULL AND awarded_amount IS NOT NULL AND awarded_amount >= 0))
            AND
            (result_code <> 'perdu' OR
                (winner_company_name IS NOT NULL AND winner_company_name <> ''
                 AND winner_amount IS NOT NULL AND winner_amount >= 0
                 AND decision_observations IS NOT NULL AND decision_observations <> ''))
            AND
            (result_code NOT IN ('annule', 'infructueux') OR
                (decision_reason IS NOT NULL AND decision_reason <> ''))
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE market_result_validations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    qualification_id BIGINT UNSIGNED NOT NULL,
    validation_decision VARCHAR(20) NOT NULL
        COMMENT 'validee ou refusee',
    validated_by INT UNSIGNED NOT NULL,
    validated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validation_reason TEXT NULL
        COMMENT 'Motif obligatoire en cas de refus',
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_result_validation_qualification (qualification_id),
    KEY idx_market_result_validations_user (validated_by),
    CONSTRAINT fk_market_result_validations_qualification
        FOREIGN KEY (qualification_id) REFERENCES market_result_qualifications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_result_validations_user
        FOREIGN KEY (validated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_result_validation_decision
        CHECK (validation_decision IN ('validee', 'refusee')),
    CONSTRAINT chk_market_result_validation_reason
        CHECK (validation_decision <> 'refusee'
            OR (validation_reason IS NOT NULL AND validation_reason <> ''))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relie les événements de qualification/validation à leur proposition.
ALTER TABLE market_transitions
    ADD COLUMN qualification_id BIGINT UNSIGNED NULL
        COMMENT 'Qualification concernée pour les événements qualification/validation',
    ADD KEY idx_market_transitions_qualification (qualification_id),
    ADD CONSTRAINT fk_market_transitions_qualification
        FOREIGN KEY (qualification_id) REFERENCES market_result_qualifications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- Identifie la qualification qui a produit le résultat officiel du marché.
-- Reste NULL pour les marchés historiques ou sans décision validée.
ALTER TABLE markets
    ADD COLUMN official_result_qualification_id BIGINT UNSIGNED NULL
        COMMENT 'Qualification validée à l origine du résultat officiel',
    ADD KEY idx_markets_official_result_qualification (official_result_qualification_id),
    ADD CONSTRAINT fk_markets_official_result_qualification
        FOREIGN KEY (official_result_qualification_id)
        REFERENCES market_result_qualifications(id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW TABLES LIKE 'market_result_%';
-- SHOW COLUMNS FROM markets LIKE 'official_result_qualification_id';
-- SHOW COLUMNS FROM market_transitions LIKE 'qualification_id';
-- SELECT COUNT(*) AS qualifications FROM market_result_qualifications;
-- SELECT COUNT(*) AS validations FROM market_result_validations;
--
-- Aucune qualification n'est créée automatiquement pour les anciens marchés.
-- Les statuts historiques et les décisions déjà présentes restent inchangés.

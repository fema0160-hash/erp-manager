-- ERP Manager — migration 004 : qualification et validation des résultats
-- Référence : erp-manager/database/migrations/004_result_qualification_validation.sql
--
-- STATUT : BROUILLON CORRIGÉ À APPROUVER — NE PAS EXÉCUTER AVANT AUTORISATION.
--
-- PRÉREQUIS :
--   - migrations 001_create_demo_tables.sql, 002_create_auth_tables.sql
--     et 003_market_lifecycle.sql installées ;
--   - sauvegarde complète vérifiée ;
--   - tables/colonnes/contraintes de cette migration absentes.
--
-- CIBLE : MariaDB 10.4.32, InnoDB, utf8mb4.
-- Les DDL MariaDB provoquent des commits implicites. Si une instruction échoue,
-- arrêter, contrôler précisément le schéma et ne pas relancer aveuglément.
--
-- CONCEPTION :
--   - une qualification est une proposition immuable ;
--   - son état est dérivé : sans ligne de validation = en attente ;
--     avec validation_decision = validee/refusee, l'état est définitif ;
--   - une qualification ne reçoit qu'une seule décision de validation ;
--   - après un refus, une nouvelle qualification est créée ;
--   - l'application doit verrouiller la ligne du marché (SELECT ... FOR UPDATE)
--     avant de vérifier qu'aucune autre qualification n'est en attente ;
--   - l'application doit écrire validation, transition et résultat officiel
--     dans une même transaction ;
--   - tous les utilisateurs autorisés sur le marché peuvent qualifier et
--     valider par défaut ; la même personne peut faire les deux actions.
--
-- IMPORTANT : cette migration ne modifie ni les statuts des marchés ni leurs
-- données existantes. Aucune qualification historique n'est inventée.

SET NAMES utf8mb4;

-- Droits dédiés : actifs par défaut pour tous les utilisateurs autorisés.
-- Auth::hasPermission applique les restrictions par utilisateur et entreprise.
INSERT IGNORE INTO permissions (code, label, description) VALUES
('markets.lifecycle.advance', 'Faire avancer le cycle de vie', 'Passer un marché à la phase suivante'),
('markets.results.qualify', 'Qualifier un résultat', 'Proposer le résultat officiel d’un marché'),
('markets.results.validate', 'Valider un résultat', 'Valider ou refuser une qualification'),
('markets.lifecycle.reopen', 'Réouvrir un marché', 'Réouverture exceptionnelle réservée à GEST');

CREATE TABLE market_result_qualifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    result_code VARCHAR(20) NOT NULL
        COMMENT 'attribue, perdu, annule ou infructueux',
    decision_date DATE NULL,
    decision_reason TEXT NULL,
    winner_company_name VARCHAR(180) NULL,
    winner_amount DECIMAL(15,2) NULL,
    decision_observations TEXT NULL,
    awarded_amount DECIMAL(15,2) NULL,
    qualified_by INT UNSIGNED NOT NULL,
    qualified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_result_qualification_id_market (id, market_id),
    KEY idx_market_result_qualifications_market_date (market_id, qualified_at),
    KEY idx_market_result_qualifications_user (qualified_by),
    CONSTRAINT fk_market_result_qualifications_market
        FOREIGN KEY (market_id) REFERENCES markets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_result_qualifications_user
        FOREIGN KEY (qualified_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_result_qualification_code
        CHECK (result_code IN ('attribue', 'perdu', 'annule', 'infructueux')),
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

-- Le couple qualification_id/market_id empêche de rattacher à tort
-- une qualification d'un marché A à l'historique d'un marché B.
ALTER TABLE market_transitions
    ADD COLUMN qualification_id BIGINT UNSIGNED NULL
        COMMENT 'Qualification liée à l événement, si applicable',
    ADD KEY idx_market_transitions_qualification_market (qualification_id, market_id),
    ADD CONSTRAINT fk_market_transitions_qualification_market
        FOREIGN KEY (qualification_id, market_id)
        REFERENCES market_result_qualifications(id, market_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- Le couple qualification officielle/marché garantit que la décision
-- officielle référencée appartient bien au même marché.
ALTER TABLE markets
    ADD COLUMN official_result_qualification_id BIGINT UNSIGNED NULL
        COMMENT 'Qualification validée à l origine du résultat officiel',
    ADD KEY idx_markets_official_result_qualification_market
        (official_result_qualification_id, id),
    ADD CONSTRAINT fk_markets_official_result_qualification_market
        FOREIGN KEY (official_result_qualification_id, id)
        REFERENCES market_result_qualifications(id, market_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Une qualification est en attente si aucune ligne correspondante n'existe
--    dans market_result_validations.
-- 2. Avant une qualification : verrouiller le marché et vérifier qu'aucune
--    qualification sans validation n'existe déjà pour ce marché.
-- 3. Avant validation : verrouiller le marché et la qualification ; vérifier
--    qu'elle n'a pas déjà été validée/refusée et que le marché est en phase 5.
-- 4. Si refus : motif obligatoire, écrire la validation et l'événement
--    historique ; ne pas modifier le statut/la phase officiels.
-- 5. Si validation : écrire la validation, mettre à jour le résultat officiel,
--    synchroniser status/phase historiques et écrire l'événement de décision
--    dans une transaction unique.
-- 6. Seule une attribution validée autorise le passage en phase 6.
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW TABLES LIKE 'market_result_%';
-- SHOW COLUMNS FROM markets LIKE 'official_result_qualification_id';
-- SHOW COLUMNS FROM market_transitions LIKE 'qualification_id';
-- SELECT COUNT(*) AS qualifications FROM market_result_qualifications;
-- SELECT COUNT(*) AS validations FROM market_result_validations;
--
-- Aucun UPDATE/DELETE/INSERT de données existantes n'est exécuté par ce fichier.

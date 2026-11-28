-- ERP Manager — migration 005 : lots indépendants et résultats par lot
-- Référence : erp-manager/database/migrations/005_market_lots_independent.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001, 002, 003 et 004 installées ;
--   - schéma réel comparé au dépôt et précontrôles exécutés ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - version applicative compatible avec market_lots déployée dans la même
--     fenêtre de maintenance. Le code actuel fonctionne au niveau MARKET et
--     doit être adapté pour lire/écrire l'état du LOT avant d'utiliser ce schéma.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
-- DDL MariaDB avec commits implicites : en cas d'échec, ARRÊTER, inspecter le
-- schéma et reprendre depuis un plan de reprise. Ne jamais relancer aveuglément.
--
-- OBJECTIFS :
--   1. Créer automatiquement un lot n° 1 pour chaque marché existant.
--   2. Permettre plusieurs lots indépendants par marché.
--   3. Rattacher chaque qualification, transition et résultat officiel à un lot.
--   4. Conserver les colonnes et liens historiques au niveau MARKET pour compatibilité.
--
-- IMPORTANT :
--   - Cette migration est additive ; elle ne supprime aucune colonne historique.
--   - Les lots existants sont initialisés depuis l'état actuel du marché.
--   - Elle ne crée pas de nouvelles qualifications et n'invente aucun résultat.
--   - Le service métier doit verrouiller la ligne LOT (SELECT ... FOR UPDATE)
--     lors des qualifications, validations et transitions.
--   - L'unicité d'une qualification en attente par lot est contrôlée en transaction
--     par le service applicatif ; MariaDB 10.4 ne fournit pas d'index partiel adapté.

SET NAMES utf8mb4;

-- PRÉCONTRÔLES À EXÉCUTER MANUELLEMENT AVANT CE SCRIPT :
-- SHOW CREATE TABLE markets;
-- SHOW CREATE TABLE market_transitions;
-- SHOW CREATE TABLE market_result_qualifications;
-- SHOW CREATE TABLE market_result_validations;
-- SELECT COUNT(*) FROM markets;
-- SELECT COUNT(*) FROM market_result_qualifications;
-- SELECT COUNT(*) FROM market_transitions;
-- Vérifier que markets.company_id est INT UNSIGNED et que les tables 004 existent.
-- Vérifier l'absence des colonnes/tables/index/contraintes créés ci-dessous.

-- 1. Clé candidate nécessaire aux relations multi-sociétés.
ALTER TABLE markets
    ADD UNIQUE KEY uq_markets_id_company (id, company_id);

-- 2. Lots : un lot initial par marché, puis lots additionnels.
CREATE TABLE market_lots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_number SMALLINT UNSIGNED NOT NULL,
    lot_reference VARCHAR(100) NULL,
    lot_label VARCHAR(180) NOT NULL,
    lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'en_cours',
    lifecycle_phase TINYINT UNSIGNED NOT NULL DEFAULT 1,
    awarded_amount DECIMAL(15,2) NULL,
    official_result_qualification_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_lots_market_number (market_id, lot_number),
    UNIQUE KEY uq_market_lots_id_market_company (id, market_id, company_id),
    KEY idx_market_lots_company_status (company_id, lifecycle_status, lifecycle_phase),
    KEY idx_market_lots_market_status (market_id, lifecycle_status),
    CONSTRAINT fk_market_lots_market_company
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_lots_number CHECK (lot_number > 0),
    CONSTRAINT chk_market_lots_phase CHECK (lifecycle_phase BETWEEN 1 AND 8),
    CONSTRAINT chk_market_lots_status CHECK (lifecycle_status IN (
        'en_cours', 'attribue', 'perdu', 'annule',
        'infructueux', 'cloture', 'a_qualifier'
    )),
    CONSTRAINT chk_market_lots_awarded_amount CHECK (
        awarded_amount IS NULL OR awarded_amount >= 0
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Création du lot n° 1 pour chaque marché. Les champs du lot restent
--    distincts de markets.reference qui demeure la référence historique.
INSERT INTO market_lots (
    market_id, company_id, lot_number, lot_reference, lot_label,
    lifecycle_status, lifecycle_phase, awarded_amount, created_at, updated_at
)
SELECT
    m.id,
    m.company_id,
    1,
    NULL,
    'Lot unique',
    m.lifecycle_status,
    m.lifecycle_phase,
    m.awarded_amount,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM markets m
WHERE NOT EXISTS (
    SELECT 1 FROM market_lots l
    WHERE l.market_id = m.id AND l.lot_number = 1
);

-- 4. Rattacher les qualifications existantes au lot n° 1.
ALTER TABLE market_result_qualifications
    ADD COLUMN lot_id BIGINT UNSIGNED NULL,
    ADD COLUMN company_id INT UNSIGNED NULL;

UPDATE market_result_qualifications q
INNER JOIN market_lots l
    ON l.market_id = q.market_id AND l.lot_number = 1
SET q.lot_id = l.id,
    q.company_id = l.company_id;

-- Contrôle bloquant : ce SELECT doit retourner 0 avant de poursuivre.
-- SELECT COUNT(*) AS qualifications_sans_lot
-- FROM market_result_qualifications
-- WHERE lot_id IS NULL OR company_id IS NULL;

ALTER TABLE market_result_qualifications
    MODIFY lot_id BIGINT UNSIGNED NOT NULL,
    MODIFY company_id INT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_market_result_qualification_lot (id, market_id, lot_id),
    ADD KEY idx_market_result_qualification_lot_date (lot_id, qualified_at),
    ADD CONSTRAINT fk_market_result_qualification_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- 5. Rattacher l'historique existant au lot n° 1 ; les événements de qualification
--    pointent vers le même lot que leur qualification.
ALTER TABLE market_transitions
    ADD COLUMN lot_id BIGINT UNSIGNED NULL,
    ADD COLUMN company_id INT UNSIGNED NULL;

UPDATE market_transitions t
INNER JOIN market_lots l
    ON l.market_id = t.market_id AND l.lot_number = 1
SET t.lot_id = l.id,
    t.company_id = l.company_id;

UPDATE market_transitions t
INNER JOIN market_result_qualifications q
    ON q.id = t.qualification_id AND q.market_id = t.market_id
SET t.lot_id = q.lot_id,
    t.company_id = q.company_id
WHERE t.qualification_id IS NOT NULL;

-- Contrôle bloquant : ce SELECT doit retourner 0 avant de poursuivre.
-- SELECT COUNT(*) AS transitions_sans_lot
-- FROM market_transitions
-- WHERE lot_id IS NULL OR company_id IS NULL;

ALTER TABLE market_transitions
    MODIFY lot_id BIGINT UNSIGNED NOT NULL,
    MODIFY company_id INT UNSIGNED NOT NULL,
    ADD KEY idx_market_transitions_lot_date (lot_id, changed_at),
    ADD CONSTRAINT fk_market_transitions_lot_company
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_market_transitions_qualification_lot
        FOREIGN KEY (qualification_id, market_id, lot_id)
        REFERENCES market_result_qualifications(id, market_id, lot_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- 6. Résultat officiel indépendant par lot. Le lien officiel au niveau MARKET
--    reste conservé pour compatibilité avec l'ancienne application.
ALTER TABLE market_lots
    ADD KEY idx_market_lots_official_qualification
        (official_result_qualification_id, market_id, id),
    ADD CONSTRAINT fk_market_lots_official_qualification
        FOREIGN KEY (official_result_qualification_id, market_id, id)
        REFERENCES market_result_qualifications(id, market_id, lot_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- 7. Initialiser le lien officiel du lot n° 1 à partir du lien historique,
--    uniquement si la qualification existe et a été rattachée au lot.
UPDATE market_lots l
INNER JOIN markets m ON m.id = l.market_id
INNER JOIN market_result_qualifications q
    ON q.id = m.official_result_qualification_id
   AND q.market_id = m.id
   AND q.lot_id = l.id
SET l.official_result_qualification_id = q.id
WHERE l.lot_number = 1
  AND l.official_result_qualification_id IS NULL;

-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SELECT COUNT(*) AS markets FROM markets;
-- SELECT COUNT(*) AS lots FROM market_lots;
-- SELECT COUNT(*) AS markets_sans_lot
-- FROM markets m LEFT JOIN market_lots l ON l.market_id = m.id
-- WHERE l.id IS NULL;
-- SELECT COUNT(*) AS qualifications_sans_lot
-- FROM market_result_qualifications WHERE lot_id IS NULL OR company_id IS NULL;
-- SELECT COUNT(*) AS transitions_sans_lot
-- FROM market_transitions WHERE lot_id IS NULL OR company_id IS NULL;
-- SHOW CREATE TABLE market_lots;
-- SHOW CREATE TABLE market_result_qualifications;
-- SHOW CREATE TABLE market_transitions;

-- ÉTAPES APPLICATIVES OBLIGATOIRES AVANT MISE EN SERVICE :
-- A. MarketLifecycle doit recevoir lot_id et lire/mettre à jour market_lots.
-- B. Les qualifications et validations doivent être verrouillées par lot.
-- C. La validation d'un résultat doit mettre à jour market_lots.lifecycle_status,
--    lifecycle_phase et official_result_qualification_id.
-- D. Les phases 1→5 doivent rester utilisables pour le lot n° 1 et les lots ajoutés.
-- E. Les transitions 6→8 ne doivent être autorisées que pour le lot attribué.
-- F. Les listes, fiches, tableaux de bord et contrôles d'accès doivent filtrer
--    les lots selon le marché et la société.
--
-- Cette migration n'est pas autorisée à l'exécution par sa simple présence dans Git.

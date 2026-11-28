-- ERP Manager — migration 008 : cautions successives par lot
-- Référence : erp-manager/database/migrations/008_market_guarantees.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 007 installées et contrôlées ;
--   - tables market_lots et stored_documents disponibles ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence de la table et de ses contraintes.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
--
-- CONCEPTION :
--   - plusieurs cautions provisoires et définitives successives par lot ;
--   - chaque remplacement crée une nouvelle ligne ;
--   - l'ancienne caution reste conservée et consultable ;
--   - un justificatif doit appartenir au même marché et à la même société.

SET NAMES utf8mb4;

CREATE TABLE market_guarantees (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    guarantee_type VARCHAR(24) NOT NULL,
    sequence_number SMALLINT UNSIGNED NOT NULL,
    replaces_guarantee_id BIGINT UNSIGNED NULL,
    required_amount DECIMAL(15,2) NOT NULL,
    obtained_amount DECIMAL(15,2) NULL,
    preparation_status VARCHAR(20) NOT NULL DEFAULT 'a_preparer',
    reference_number VARCHAR(100) NULL,
    prepared_at DATETIME NULL,
    obtained_at DATETIME NULL,
    expires_at DATE NULL,
    released_at DATETIME NULL,
    document_id BIGINT UNSIGNED NULL,
    observations TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_guarantees_lot_type_sequence
        (lot_id, guarantee_type, sequence_number),
    UNIQUE KEY uq_market_guarantees_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_guarantees_lot_status (lot_id, guarantee_type, preparation_status),
    KEY idx_market_guarantees_expiry (company_id, preparation_status, expires_at),
    KEY idx_market_guarantees_document (document_id, market_id, company_id),
    KEY idx_market_guarantees_replacement
        (replaces_guarantee_id, lot_id, market_id, company_id),
    CONSTRAINT fk_market_guarantees_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_guarantees_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_guarantees_replacement
        FOREIGN KEY (replaces_guarantee_id, lot_id, market_id, company_id)
        REFERENCES market_guarantees(id, lot_id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_guarantees_type
        CHECK (guarantee_type IN ('provisoire', 'definitive')),
    CONSTRAINT chk_market_guarantees_sequence
        CHECK (sequence_number > 0),
    CONSTRAINT chk_market_guarantees_amounts
        CHECK (
            required_amount >= 0
            AND (obtained_amount IS NULL OR obtained_amount >= 0)
        ),
    CONSTRAINT chk_market_guarantees_status
        CHECK (preparation_status IN (
            'a_preparer', 'preparee', 'obtenue', 'liberee', 'restituee'
        )),
    CONSTRAINT chk_market_guarantees_dates
        CHECK (
            (obtained_at IS NULL OR prepared_at IS NOT NULL)
            AND (released_at IS NULL OR obtained_at IS NOT NULL)
        ),
    CONSTRAINT chk_market_guarantees_not_self_replacement
        CHECK (replaces_guarantee_id IS NULL OR replaces_guarantee_id <> id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Une nouvelle caution remplaçant une précédente reçoit un nouvel ID et
--    un sequence_number supérieur pour le même lot et le même type.
-- 2. replaces_guarantee_id doit viser une caution du même lot, marché et société.
--    Le service doit également interdire les cycles de remplacement.
-- 3. Ne jamais écraser une caution existante lors d'un renouvellement bancaire.
-- 4. Le passage à obtenue exige une date d'obtention et les informations
--    justificatives requises par la procédure interne.
-- 5. La libération/restitution ne modifie pas les références historiques.
-- 6. Les documents sont privés et vérifiés par le contrôleur de téléchargement.
-- 7. Le taux de retenue de garantie et son plafond ne sont pas stockés ici :
--    ils sont figés par lot dans market_award_details (migration 006).
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW CREATE TABLE market_guarantees;
-- SELECT lot_id, guarantee_type, COUNT(*) AS total
-- FROM market_guarantees GROUP BY lot_id, guarantee_type;
--
-- Aucune caution existante n'est modifiée ou supprimée par ce script.

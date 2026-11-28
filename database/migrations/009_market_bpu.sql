-- ERP Manager — migration 009 : BPU par lot, versions, lignes et imports
-- Référence : erp-manager/database/migrations/009_market_bpu.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 008 installées et contrôlées ;
--   - market_lots et stored_documents disponibles ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence des tables et contraintes.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
--
-- RÈGLES :
--   - chaque version BPU est propre à un lot ;
--   - les versions antérieures sont conservées ;
--   - les lignes conservent leur taux TVA ;
--   - HT, TVA et TTC sont recalculés côté serveur, jamais acceptés comme
--     valeurs fiables depuis le navigateur ou un fichier importé ;
--   - une version finalisée est verrouillée côté service métier.

SET NAMES utf8mb4;

-- 1. Versions BPU par lot.
CREATE TABLE market_bpu_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    version_number SMALLINT UNSIGNED NOT NULL,
    version_status VARCHAR(16) NOT NULL DEFAULT 'brouillon',
    source_document_id BIGINT UNSIGNED NULL,
    total_ht DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_ttc DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    version_note VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalized_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_bpu_versions_lot_number (lot_id, version_number),
    UNIQUE KEY uq_market_bpu_versions_id_market_company (id, market_id, company_id),
    UNIQUE KEY uq_market_bpu_versions_id_lot_market_company
        (id, lot_id, market_id, company_id),
    KEY idx_market_bpu_versions_lot_status (lot_id, version_status, version_number),
    KEY idx_market_bpu_versions_source_document (source_document_id, market_id, company_id),
    KEY idx_market_bpu_versions_created_by (created_by),
    CONSTRAINT fk_market_bpu_versions_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_bpu_versions_source_document
        FOREIGN KEY (source_document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_bpu_versions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_bpu_versions_number CHECK (version_number > 0),
    CONSTRAINT chk_market_bpu_versions_status
        CHECK (version_status IN ('brouillon', 'importee', 'finale', 'archivee')),
    CONSTRAINT chk_market_bpu_versions_totals
        CHECK (total_ht >= 0 AND total_vat >= 0 AND total_ttc >= 0),
    CONSTRAINT chk_market_bpu_versions_finalized
        CHECK (
            (version_status = 'finale' AND finalized_at IS NOT NULL)
            OR (version_status <> 'finale')
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Lignes de chaque version.
CREATE TABLE market_bpu_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bpu_version_id BIGINT UNSIGNED NOT NULL,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    line_number INT UNSIGNED NOT NULL,
    item_code VARCHAR(60) NULL,
    designation TEXT NOT NULL,
    unit VARCHAR(30) NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price_ht DECIMAL(15,4) NOT NULL,
    vat_rate_percent DECIMAL(5,2) NOT NULL,
    line_total_ht DECIMAL(15,2) NOT NULL,
    line_total_vat DECIMAL(15,2) NOT NULL,
    line_total_ttc DECIMAL(15,2) NOT NULL,
    source_row_number INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_bpu_items_version_line (bpu_version_id, line_number),
    KEY idx_market_bpu_items_version_company (bpu_version_id, market_id, company_id),
    CONSTRAINT fk_market_bpu_items_version
        FOREIGN KEY (bpu_version_id, market_id, company_id)
        REFERENCES market_bpu_versions(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_bpu_items_line_number CHECK (line_number > 0),
    CONSTRAINT chk_market_bpu_items_values CHECK (
        quantity >= 0
        AND unit_price_ht >= 0
        AND vat_rate_percent BETWEEN 0 AND 100
        AND line_total_ht >= 0
        AND line_total_vat >= 0
        AND line_total_ttc >= 0
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Journal d'importation : un import n'écrase jamais une version antérieure.
CREATE TABLE market_bpu_imports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    bpu_version_id BIGINT UNSIGNED NOT NULL,
    source_document_id BIGINT UNSIGNED NOT NULL,
    import_status VARCHAR(20) NOT NULL DEFAULT 'en_cours',
    rows_total INT UNSIGNED NOT NULL DEFAULT 0,
    rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
    rows_rejected INT UNSIGNED NOT NULL DEFAULT 0,
    error_report_document_id BIGINT UNSIGNED NULL,
    imported_by INT UNSIGNED NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_bpu_imports_market_date (market_id, imported_at),
    KEY idx_market_bpu_imports_version (bpu_version_id, market_id, company_id),
    KEY idx_market_bpu_imports_source (source_document_id, market_id, company_id),
    KEY idx_market_bpu_imports_error_report (error_report_document_id, market_id, company_id),
    KEY idx_market_bpu_imports_user (imported_by),
    CONSTRAINT fk_market_bpu_imports_version
        FOREIGN KEY (bpu_version_id, market_id, company_id)
        REFERENCES market_bpu_versions(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_bpu_imports_source_document
        FOREIGN KEY (source_document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_bpu_imports_error_report
        FOREIGN KEY (error_report_document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_bpu_imports_user
        FOREIGN KEY (imported_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_bpu_imports_status
        CHECK (import_status IN ('en_cours', 'termine', 'partiel', 'rejete')),
    CONSTRAINT chk_market_bpu_imports_counts
        CHECK (rows_imported <= rows_total AND rows_rejected <= rows_total
            AND rows_imported + rows_rejected <= rows_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Créer une nouvelle version pour chaque nouvel import ou révision ; ne pas
--    écraser les versions précédentes.
-- 2. Calcul par ligne avec décimales exactes :
--      HT  = ARRONDI(quantity * unit_price_ht, 2)
--      TVA = ARRONDI(HT * vat_rate_percent / 100, 2)
--      TTC = HT + TVA
--    La règle d'arrondi doit être centralisée dans le service métier.
-- 3. Recalculer les totaux de version depuis les lignes dans une transaction.
-- 4. Une seule version finale active par lot : verrouiller le lot et contrôler
--    les versions existantes avant finalisation ; MariaDB 10.4 n'a pas d'index
--    partiel pour imposer directement cette règle.
-- 5. Après finalisation, interdire toute modification/suppression des lignes
--    et métadonnées financières ; toute correction crée une nouvelle version.
-- 6. Vérifier l'entreprise, le marché, le lot et les droits avant chaque lecture.
-- 7. Un import partiel ou rejeté conserve le rapport d'erreurs comme document
--    privé et ne finalise pas automatiquement la version.
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW CREATE TABLE market_bpu_versions;
-- SHOW CREATE TABLE market_bpu_items;
-- SHOW CREATE TABLE market_bpu_imports;
--
-- Aucune version BPU ou ligne historique n'est modifiée par ce script.

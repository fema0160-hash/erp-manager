-- ERP Manager — migration 007 : documents privés, modèles et checklist
-- Référence : erp-manager/database/migrations/007_market_documents_checklist.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 006 installées et contrôlées ;
--   - markets(id, company_id) et market_lots disponibles ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence des tables, index, contraintes et triggers.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
-- Les fichiers physiques doivent rester hors du webroot. Ce script ne crée
-- aucun répertoire et ne déplace aucun fichier.
--
-- SÉCURITÉ :
--   - storage_key est une clé générée côté serveur, jamais un chemin fourni
--     par le navigateur ;
--   - MIME, extension, taille et SHA-256 sont contrôlés côté serveur ;
--   - le téléchargement passe par un contrôleur authentifié et autorisé ;
--   - la corbeille est logique ; aucune suppression physique automatique ;
--   - les événements de checklist sont append-only, y compris au niveau SQL.

SET NAMES utf8mb4;

-- 1. Modèles documentaires propres à chaque société.
CREATE TABLE market_document_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    template_name VARCHAR(160) NOT NULL,
    template_version INT UNSIGNED NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_document_templates_id_company (id, company_id),
    UNIQUE KEY uq_market_document_templates_version (company_id, template_name, template_version),
    KEY idx_market_document_templates_active (company_id, is_active, is_default),
    CONSTRAINT fk_market_document_templates_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_document_templates_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_document_templates_version CHECK (template_version > 0),
    CONSTRAINT chk_market_document_templates_flags CHECK (
        is_default IN (0, 1) AND is_active IN (0, 1)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE market_document_template_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_id BIGINT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    designation VARCHAR(180) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_document_template_items_order (template_id, sort_order),
    UNIQUE KEY uq_market_document_template_items_id_template_company (id, template_id, company_id),
    KEY idx_market_document_template_items_company (company_id, template_id, is_active),
    CONSTRAINT fk_market_document_template_items_template
        FOREIGN KEY (template_id, company_id)
        REFERENCES market_document_templates(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_document_template_items_flags CHECK (
        is_required IN (0, 1) AND is_active IN (0, 1)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Métadonnées des fichiers privés liés à un marché.
CREATE TABLE stored_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    storage_key VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    extension VARCHAR(12) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_stored_documents_id_market_company (id, market_id, company_id),
    UNIQUE KEY uq_stored_documents_storage_key (storage_key),
    KEY idx_stored_documents_market_uploaded (market_id, uploaded_at),
    KEY idx_stored_documents_company_deleted (company_id, is_deleted, deleted_at),
    KEY idx_stored_documents_uploaded_by (uploaded_by),
    CONSTRAINT fk_stored_documents_market_company
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stored_documents_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_stored_documents_size CHECK (size_bytes > 0),
    CONSTRAINT chk_stored_documents_deleted CHECK (
        (is_deleted = 0 AND deleted_at IS NULL)
        OR (is_deleted = 1 AND deleted_at IS NOT NULL)
    ),
    CONSTRAINT chk_stored_documents_sha256 CHECK (sha256 REGEXP '^[0-9a-fA-F]{64}$'),
    CONSTRAINT chk_stored_documents_extension CHECK (extension <> '')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La preuve de dépôt est déclarée en 006 ; sa FK est ajoutée maintenant que
-- stored_documents existe. Le document doit appartenir au même marché/société.
ALTER TABLE market_submissions
    ADD KEY idx_market_submissions_proof_document
        (proof_document_id, market_id, company_id),
    ADD CONSTRAINT fk_market_submissions_proof_document
        FOREIGN KEY (proof_document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- 3. Checklist : copie figée du modèle dans le dossier marché.
-- source_template_id et source_template_item_id sont des références historiques
-- sans FK active : la suppression/évolution d'un modèle ne modifie pas le dossier.
CREATE TABLE market_document_checklist_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    source_template_id BIGINT UNSIGNED NULL,
    source_template_item_id BIGINT UNSIGNED NULL,
    designation_snapshot VARCHAR(180) NOT NULL,
    sort_order_snapshot SMALLINT UNSIGNED NOT NULL,
    is_required_snapshot TINYINT(1) NOT NULL DEFAULT 0,
    is_checked TINYINT(1) NOT NULL DEFAULT 0,
    checked_at DATETIME NULL,
    checked_by INT UNSIGNED NULL,
    document_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_document_checklist_order (market_id, sort_order_snapshot),
    UNIQUE KEY uq_market_document_checklist_id_market_company (id, market_id, company_id),
    KEY idx_market_document_checklist_company (company_id, market_id, is_checked),
    KEY idx_market_document_checklist_document (document_id, market_id, company_id),
    KEY idx_market_document_checklist_checked_by (checked_by),
    CONSTRAINT fk_market_document_checklist_market
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_document_checklist_checked_by
        FOREIGN KEY (checked_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_document_checklist_document
        FOREIGN KEY (document_id, market_id, company_id)
        REFERENCES stored_documents(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_document_checklist_flags CHECK (
        is_required_snapshot IN (0, 1)
        AND is_checked IN (0, 1)
        AND (
            (is_checked = 0 AND checked_at IS NULL AND checked_by IS NULL)
            OR (is_checked = 1 AND checked_at IS NOT NULL AND checked_by IS NOT NULL)
        )
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Historique immuable des coches/décoches.
CREATE TABLE market_document_checklist_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    checklist_item_id BIGINT UNSIGNED NOT NULL,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    event_code VARCHAR(12) NOT NULL,
    event_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_market_document_checklist_events_item_date (checklist_item_id, event_at),
    KEY idx_market_document_checklist_events_market_date (market_id, event_at),
    KEY idx_market_document_checklist_events_user (user_id),
    CONSTRAINT fk_market_document_checklist_events_item
        FOREIGN KEY (checklist_item_id, market_id, company_id)
        REFERENCES market_document_checklist_items(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_document_checklist_events_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_document_checklist_events_code
        CHECK (event_code IN ('checked', 'unchecked'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Protection SQL contre la modification ou suppression de l'historique.
DELIMITER //
CREATE TRIGGER trg_market_document_checklist_events_no_update
BEFORE UPDATE ON market_document_checklist_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Historique checklist immuable : UPDATE interdit';
END//

CREATE TRIGGER trg_market_document_checklist_events_no_delete
BEFORE DELETE ON market_document_checklist_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Historique checklist immuable : DELETE interdit';
END//
DELIMITER ;

-- RÈGLES APPLICATIVES OBLIGATOIRES :
-- 1. Création du dossier : copier les lignes du modèle actif dans
--    market_document_checklist_items, sans conserver de FK vers le modèle.
-- 2. Une coche/décoche doit mettre à jour l'état courant et insérer son événement
--    dans une transaction unique, après verrouillage de la ligne checklist.
-- 3. L'heure est générée par le serveur ; configurer PHP en Africa/Casablanca
--    et conserver la même référence temporelle côté base/application.
-- 4. Édition normale autorisée pendant les phases 3 et 4. Après confirmation du
--    dépôt ou passage en phase 5, refuser les changements côté serveur.
-- 5. Une correction exceptionnelle après verrouillage exige un motif et un
--    nouvel événement ; aucun ancien événement n'est modifié.
-- 6. Une pièce jointe doit appartenir au même marché et à la même société.
-- 7. Un seul modèle par défaut actif et par société : contrôle transactionnel
--    applicatif, car l'indexation partielle n'est pas disponible ici.
-- 8. L'accès aux documents doit vérifier l'entreprise, le marché, les droits,
--    l'état de corbeille et l'existence réelle du fichier avant téléchargement.
--
-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW TABLES LIKE 'market_document%';
-- SHOW CREATE TABLE stored_documents;
-- SHOW CREATE TABLE market_document_checklist_items;
-- SHOW CREATE TABLE market_document_checklist_events;
-- SHOW TRIGGERS LIKE 'trg_market_document_checklist_events%';
--
-- Aucune donnée historique des migrations 001 à 006 n'est modifiée.
-- Aucun fichier physique n'est créé, déplacé ou supprimé par cette migration.

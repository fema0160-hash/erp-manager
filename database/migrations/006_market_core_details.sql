-- ERP Manager — migration 006 : paramètres, organismes et données générales du marché
-- Référence : erp-manager/database/migrations/006_market_core_details.sql
--
-- STATUT : BROUILLON TECHNIQUE — NE PAS EXÉCUTER SANS AUTORISATION EXPLICITE.
--
-- PRÉREQUIS :
--   - migrations 001 à 005 installées et contrôlées ;
--   - migration 005 et version applicative compatible avec les lots déployées ;
--   - sauvegarde complète vérifiée et restauration testée ;
--   - contrôle préalable de l'absence des colonnes, tables, index et contraintes.
--
-- CIBLE : MariaDB 10.4.32 / InnoDB / utf8mb4.
-- Ce script est additif. Les DDL MariaDB provoquent des commits implicites.
-- En cas d'échec, arrêter et inspecter le schéma avant toute reprise.
--
-- Les valeurs 10 % et 7 % sont des valeurs initiales modifiables par société.
-- Le calcul effectif de la retenue sera exécuté dans la migration dédiée aux
-- situations et événements de retenue, après validation de chaque situation.

SET NAMES utf8mb4;

-- 1. Numéro interne et date de mise à jour du dossier.
ALTER TABLE markets
    ADD COLUMN internal_number VARCHAR(40) NULL
        COMMENT 'Numéro de dossier interne automatique, distinct de reference',
    ADD COLUMN updated_at DATETIME NULL
        COMMENT 'Dernière modification du dossier',
    ADD UNIQUE KEY uq_markets_company_internal_number (company_id, internal_number);

-- 2. Paramètres métier par société.
CREATE TABLE market_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    value_type VARCHAR(20) NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_settings_company_key (company_id, setting_key),
    KEY idx_market_settings_updated_by (updated_by),
    CONSTRAINT fk_market_settings_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_settings_user
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_settings_value_type
        CHECK (value_type IN ('string', 'integer', 'decimal', 'boolean'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE market_setting_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by INT UNSIGNED NOT NULL,
    change_reason VARCHAR(255) NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_market_setting_history_company_key_date
        (company_id, setting_key, changed_at),
    KEY idx_market_setting_history_user (changed_by),
    CONSTRAINT fk_market_setting_history_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_setting_history_user
        FOREIGN KEY (changed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialisation des deux paramètres de retenue pour chaque société.
-- Le compte GEST sert d'auteur technique de la valeur initiale ; s'il n'existe
-- pas, le contrôle préalable doit échouer et l'installation doit être arrêtée.
INSERT INTO market_settings (company_id, setting_key, setting_value, value_type, updated_by)
SELECT c.id, 'retention_rate_percent', '10.00', 'decimal', u.id
FROM companies c
CROSS JOIN users u
WHERE u.username = 'gest'
  AND NOT EXISTS (
      SELECT 1 FROM market_settings s
      WHERE s.company_id = c.id AND s.setting_key = 'retention_rate_percent'
  );

INSERT INTO market_settings (company_id, setting_key, setting_value, value_type, updated_by)
SELECT c.id, 'retention_cap_percent', '7.00', 'decimal', u.id
FROM companies c
CROSS JOIN users u
WHERE u.username = 'gest'
  AND NOT EXISTS (
      SELECT 1 FROM market_settings s
      WHERE s.company_id = c.id AND s.setting_key = 'retention_cap_percent'
  );

-- 3. Numérotation atomique par société, année et type de numéro.
CREATE TABLE market_number_sequences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    sequence_type VARCHAR(30) NOT NULL,
    sequence_year SMALLINT UNSIGNED NOT NULL,
    current_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
    prefix VARCHAR(20) NOT NULL DEFAULT 'M',
    number_padding TINYINT UNSIGNED NOT NULL DEFAULT 5,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_number_sequences_scope
        (company_id, sequence_type, sequence_year),
    CONSTRAINT fk_market_number_sequences_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_number_sequences_padding
        CHECK (number_padding BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Référentiel des maîtres d'ouvrage et maîtres d'œuvre par société.
CREATE TABLE market_organizations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    organization_type VARCHAR(24) NOT NULL,
    name VARCHAR(180) NOT NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(180) NULL,
    contact_name VARCHAR(160) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_organizations_id_company (id, company_id),
    KEY idx_market_organizations_company_name (company_id, name),
    CONSTRAINT fk_market_organizations_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_organizations_type
        CHECK (organization_type IN ('maitre_ouvrage', 'maitre_oeuvre', 'autre'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Informations générales complémentaires du marché.
CREATE TABLE market_details (
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    tender_number VARCHAR(100) NULL,
    client_organization_id BIGINT UNSIGNED NULL,
    project_manager_organization_id BIGINT UNSIGNED NULL,
    duration_months SMALLINT UNSIGNED NULL,
    is_renewable TINYINT(1) NOT NULL DEFAULT 0,
    renewal_duration_years SMALLINT UNSIGNED NULL,
    market_nature VARCHAR(20) NULL,
    execution_address VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (market_id),
    UNIQUE KEY uq_market_details_market_company (market_id, company_id),
    KEY idx_market_details_client (client_organization_id, company_id),
    KEY idx_market_details_project_manager (project_manager_organization_id, company_id),
    CONSTRAINT fk_market_details_market_company
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_details_client_company
        FOREIGN KEY (client_organization_id, company_id)
        REFERENCES market_organizations(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_details_project_manager_company
        FOREIGN KEY (project_manager_organization_id, company_id)
        REFERENCES market_organizations(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_details_renewable
        CHECK (
            is_renewable IN (0, 1)
            AND (duration_months IS NULL OR duration_months > 0)
            AND (
                (is_renewable = 0 AND renewal_duration_years IS NULL)
                OR (is_renewable = 1 AND renewal_duration_years IS NOT NULL AND renewal_duration_years > 0)
            )
        ),
    CONSTRAINT chk_market_details_nature
        CHECK (market_nature IS NULL OR market_nature IN ('travaux', 'fournitures', 'services'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Étude du marché : les dates sont NULL jusqu'à leur saisie en phase 2.
CREATE TABLE market_studies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    launch_date DATE NULL,
    offer_deadline_at DATETIME NULL,
    opening_at DATETIME NULL,
    study_validated_at DATETIME NULL,
    study_started_at DATETIME NULL,
    study_completed_at DATETIME NULL,
    study_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estimated_cost DECIMAL(15,2) NULL,
    study_manager_user_id INT UNSIGNED NULL,
    observations TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_studies_market (market_id),
    UNIQUE KEY uq_market_studies_id_market_company (id, market_id, company_id),
    CONSTRAINT fk_market_studies_market_company
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_studies_manager
        FOREIGN KEY (study_manager_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_studies_amounts
        CHECK (study_cost >= 0 AND (estimated_cost IS NULL OR estimated_cost >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Dépôt : une ligne par marché ; les données sont renseignées à la phase 4.
CREATE TABLE market_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    deposited_by INT UNSIGNED NULL,
    deposited_at DATETIME NULL,
    observations TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_submissions_market (market_id),
    UNIQUE KEY uq_market_submissions_id_market_company (id, market_id, company_id),
    CONSTRAINT fk_market_submissions_market_company
        FOREIGN KEY (market_id, company_id)
        REFERENCES markets(id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_market_submissions_user
        FOREIGN KEY (deposited_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_submissions_deposit_pair
        CHECK ((deposited_by IS NULL AND deposited_at IS NULL)
            OR (deposited_by IS NOT NULL AND deposited_at IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Données contractuelles et instantanés de retenue propres à chaque lot.
CREATE TABLE market_award_details (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    market_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    lot_id BIGINT UNSIGNED NOT NULL,
    official_market_number VARCHAR(100) NULL,
    contract_signed_at DATE NULL,
    service_order_number VARCHAR(100) NULL,
    service_order_date DATE NULL,
    notification_date DATE NULL,
    retention_rate_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    retention_cap_percent DECIMAL(5,2) NOT NULL DEFAULT 7.00,
    retention_base_amount_ttc DECIMAL(15,2) NULL,
    retention_cap_amount_ttc DECIMAL(15,2) NULL,
    retention_base_code VARCHAR(30) NOT NULL DEFAULT 'awarded_amount_ttc',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_market_award_details_lot (lot_id),
    UNIQUE KEY uq_market_award_details_id_lot_market_company
        (id, lot_id, market_id, company_id),
    CONSTRAINT fk_market_award_details_lot
        FOREIGN KEY (lot_id, market_id, company_id)
        REFERENCES market_lots(id, market_id, company_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_market_award_retention_rates
        CHECK (retention_rate_percent BETWEEN 0 AND 100
            AND retention_cap_percent BETWEEN 0 AND 100),
    CONSTRAINT chk_market_award_retention_amounts
        CHECK (
            (retention_base_amount_ttc IS NULL OR retention_base_amount_ttc >= 0)
            AND (retention_cap_amount_ttc IS NULL OR retention_cap_amount_ttc >= 0)
        ),
    CONSTRAINT chk_market_award_retention_base
        CHECK (retention_base_code = 'awarded_amount_ttc')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CONTRÔLES APRÈS INSTALLATION (lecture seule) :
-- SHOW TABLES LIKE 'market_%';
-- SHOW COLUMNS FROM markets LIKE 'internal_number';
-- SELECT setting_key, COUNT(*) FROM market_settings GROUP BY setting_key;
-- SELECT COUNT(*) FROM market_organizations;
-- SELECT COUNT(*) FROM market_details;
-- SELECT COUNT(*) FROM market_studies;
-- SELECT COUNT(*) FROM market_submissions;
-- SELECT COUNT(*) FROM market_award_details;
--
-- AUCUNE donnée existante de marché n'est supprimée ou réécrite par cette migration.
-- Les détails et études sont créés par l'application au fur et à mesure des saisies.
-- Le numéro interne est rempli par le service de numérotation atomique, pas par SQL.

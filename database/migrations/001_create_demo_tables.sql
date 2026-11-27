-- ERP Manager — création des tables et insertion des données de démonstration
-- Importer ce fichier dans la base erp_manager via phpMyAdmin.
-- Les INSERT IGNORE évitent de remplacer des données déjà présentes.

CREATE TABLE IF NOT EXISTS companies (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(180) NOT NULL,
    short_name VARCHAR(100) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#64748b',
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_code (code),
    CONSTRAINT chk_companies_is_demo CHECK (is_demo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS markets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(40) NOT NULL,
    object TEXT NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL,
    phase TINYINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    market_date DATE NOT NULL,
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_markets_reference (reference),
    KEY idx_markets_company (company_id),
    KEY idx_markets_status (status),
    KEY idx_markets_phase (phase),
    CONSTRAINT fk_markets_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_markets_status
        CHECK (status IN ('en_cours', 'cloture', 'gagne', 'perdu_annule', 'en_attente')),
    CONSTRAINT chk_markets_phase CHECK (phase BETWEEN 1 AND 6),
    CONSTRAINT chk_markets_amount CHECK (amount >= 0),
    CONSTRAINT chk_markets_is_demo CHECK (is_demo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cinq sociétés fictives du tableau de bord
INSERT IGNORE INTO companies (code, name, short_name, color, is_demo) VALUES
('AIT_KHAYI_DRISS', 'AIT KHAYI DRISS', 'AIT KHAYI DRISS', '#3b82f6', 1),
('ONCADEMY', 'ONCADEMY / DEUTSCH ZENTRUM', 'ONCADEMY / DEUTSCH Z.', '#10b981', 1),
('DROGUEMED', 'DROGUEMED', 'DROGUEMED', '#a855f7', 1),
('ATLAS_INFRA', 'ATLAS INFRA & SERVICES', 'ATLAS INFRA & SERVICES', '#06b6d4', 1),
('NORD_INGENIERIE', 'NORD INGÉNIERIE CONSEIL', 'NORD INGÉNIERIE CONSEIL', '#f43f5e', 1);

-- Huit marchés fictifs du tableau de bord
INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-008', 'Aménagement voirie et réseaux divers — Lot 2', c.id, 'en_attente', 1, 1250000.00, '2026-11-25', 1
FROM companies c WHERE c.code = 'AIT_KHAYI_DRISS';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-007', 'Construction annexe administrative & salles', c.id, 'en_cours', 2, 987500.00, '2026-11-21', 1
FROM companies c WHERE c.code = 'ONCADEMY';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-006', 'Fourniture équipements médicaux spécialisés', c.id, 'en_cours', 3, 750000.00, '2026-11-18', 1
FROM companies c WHERE c.code = 'DROGUEMED';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-005', 'Réhabilitation infrastructure routière régionale', c.id, 'gagne', 5, 1640000.00, '2026-11-14', 1
FROM companies c WHERE c.code = 'ATLAS_INFRA';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-004', 'Étude technique et maîtrise d’œuvre génie civil', c.id, 'en_attente', 2, 420000.00, '2026-11-10', 1
FROM companies c WHERE c.code = 'NORD_INGENIERIE';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-003', 'Travaux de terrassement et soutènement', c.id, 'en_cours', 4, 860000.00, '2026-11-06', 1
FROM companies c WHERE c.code = 'AIT_KHAYI_DRISS';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-002', 'Audit qualité et contrôle des ouvrages', c.id, 'cloture', 6, 315000.00, '2026-11-02', 1
FROM companies c WHERE c.code = 'NORD_INGENIERIE';

INSERT IGNORE INTO markets
    (reference, object, company_id, status, phase, amount, market_date, is_demo)
SELECT 'M-2026-001', 'Maintenance réseaux et génie urbain', c.id, 'perdu_annule', 5, 540000.00, '2026-10-28', 1
FROM companies c WHERE c.code = 'ATLAS_INFRA';

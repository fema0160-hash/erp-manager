-- ERP Manager — authentification, sessions et restrictions d'accès
-- À importer une seule fois après 001_create_demo_tables.sql.
-- Mot de passe initial des trois comptes : 123 (hashé avec password_hash).

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    display_name VARCHAR(160) NOT NULL,
    role VARCHAR(10) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_active (role, is_active),
    CONSTRAINT chk_users_role CHECK (role IN ('GEST', 'SECR', 'CHAD')),
    CONSTRAINT chk_users_must_change CHECK (must_change_password IN (0, 1)),
    CONSTRAINT chk_users_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    session_id_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    last_activity_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_sessions_hash (session_id_hash),
    KEY idx_user_sessions_user (user_id),
    KEY idx_user_sessions_expiry (expires_at),
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(100) NOT NULL,
    label VARCHAR(160) NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_company_restrictions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    restricted_by INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_company_restriction (user_id, company_id),
    KEY idx_company_restrictions_company (company_id),
    CONSTRAINT fk_company_restrictions_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_company_restrictions_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_company_restrictions_admin FOREIGN KEY (restricted_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_permission_restrictions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    restricted_by INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_permission_restriction (user_id, company_id, permission_id),
    KEY idx_permission_restrictions_company (company_id),
    CONSTRAINT fk_permission_restrictions_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_permission_restrictions_company FOREIGN KEY (company_id)
        REFERENCES companies(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_permission_restrictions_permission FOREIGN KEY (permission_id)
        REFERENCES permissions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_permission_restrictions_admin FOREIGN KEY (restricted_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (code, label, description) VALUES
('dashboard.view', 'Consulter le tableau de bord', 'Accès au tableau de bord'),
('markets.view', 'Consulter les marchés', 'Afficher les marchés et leurs fiches'),
('markets.create', 'Créer un marché', 'Ajouter un nouveau marché'),
('markets.update', 'Modifier un marché', 'Modifier les informations d’un marché'),
('markets.delete', 'Supprimer un marché', 'Supprimer un marché'),
('companies.view', 'Consulter les entreprises', 'Afficher les entreprises'),
('companies.create', 'Créer une entreprise', 'Ajouter une entreprise'),
('companies.update', 'Modifier les entreprises', 'Modifier une entreprise'),
('users.manage', 'Gérer les utilisateurs', 'Créer et modifier les comptes utilisateurs'),
('permissions.manage', 'Gérer les autorisations', 'Attribuer ou retirer des accès'),
('archives.view', 'Consulter les archives', 'Accéder aux archives'),
('cautions.view', 'Consulter les cautions et RG', 'Accéder aux cautions et retenues de garantie'),
('charges.view', 'Consulter les charges', 'Accéder aux charges'),
('vehicles.view', 'Consulter les véhicules', 'Accéder aux véhicules'),
('deadlines.view', 'Consulter les échéances', 'Accéder aux échéances'),
('notes.view', 'Consulter les notes', 'Accéder aux notes'),
('settings.manage', 'Gérer les paramètres', 'Accéder aux paramètres de l’application');

-- Identifiants initiaux : gest, secr et chad ; mot de passe initial 123.
-- Hash bcrypt de 123 ; aucun mot de passe en clair n'est stocké.
INSERT IGNORE INTO users (username, display_name, role, password_hash, must_change_password, is_active)
VALUES
('gest', 'Administrateur général', 'GEST', '$2y$12$FPeCH7VGhCNb5fcwyw/2SOc6/moVXmXRyiqej8Uh/RboJacDFzu1m', 1, 1),
('secr', 'Secrétaire', 'SECR', '$2y$12$FPeCH7VGhCNb5fcwyw/2SOc6/moVXmXRyiqej8Uh/RboJacDFzu1m', 1, 1),
('chad', 'Chargé d’études', 'CHAD', '$2y$12$FPeCH7VGhCNb5fcwyw/2SOc6/moVXmXRyiqej8Uh/RboJacDFzu1m', 1, 1);

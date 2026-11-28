<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Casablanca');

$pdo = null;
$auth = null;
$databaseUnavailable = false;
$errorMessage = '';
$currentUser = null;

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/app/Auth.php';
    require_once dirname(__DIR__) . '/app/MarketLifecycle.php';
    $auth = new Auth($pdo);
    $auth->start();
} catch (Throwable $exception) {
    error_log('ERP Manager - initialisation : ' . $exception->getMessage());
    $databaseUnavailable = true;
}

$page = (string) ($_GET['page'] ?? 'dashboard');
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($databaseUnavailable || !($auth instanceof Auth)) {
    require dirname(__DIR__) . '/resources/views/login.php';
    exit;
}

if ($page === 'login') {
    if ($isPost) {
        if (!$auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
            $errorMessage = 'Votre session a expiré. Rechargez la page et réessayez.';
        } elseif ($auth->login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            header('Location: ?page=dashboard');
            exit;
        } else {
            $errorMessage = 'Identifiant ou mot de passe incorrect, ou compte temporairement bloqué.';
        }
    } elseif ($auth->currentUser()) {
        header('Location: ?page=dashboard');
        exit;
    }
    require dirname(__DIR__) . '/resources/views/login.php';
    exit;
}

if ($page === 'logout') {
    if ($isPost && $auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
        $auth->logout();
    }
    header('Location: ?page=login');
    exit;
}

if ($page === 'utilisateurs') {
    $currentUser = $auth->requireLogin();
    if (($currentUser['role'] ?? '') !== 'GEST') {
        http_response_code(403);
        echo 'Accès refusé. Cette page est réservée à l’administrateur général.';
        exit;
    }

    $successMessage = '';
    if (isset($_GET['success'])) {
        $successMessage = 'Opération effectuée avec succès.';
    }

    if ($isPost) {
        if (!$auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
            $errorMessage = 'Votre session a expiré. Rechargez la page et réessayez.';
        } else {
            $action = (string) ($_POST['action'] ?? '');
            try {
                if ($action === 'create_user') {
                    $username = trim((string) ($_POST['username'] ?? ''));
                    $displayName = trim((string) ($_POST['display_name'] ?? ''));
                    $role = (string) ($_POST['role'] ?? '');
                    if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username) || $displayName === ''
                        || !in_array($role, ['SECR', 'CHAD'], true)) {
                        throw new InvalidArgumentException('Vérifiez l’identifiant, le nom et le rôle du nouvel utilisateur.');
                    }
                    $hash = password_hash('123', PASSWORD_DEFAULT);
                    $q = $pdo->prepare(
                        'INSERT INTO users (username, display_name, role, password_hash, must_change_password, is_active)
                         VALUES (:username, :display_name, :role, :hash, 1, 1)'
                    );
                    $q->execute(['username' => $username, 'display_name' => $displayName, 'role' => $role, 'hash' => $hash]);
                    header('Location: ?page=utilisateurs&success=1');
                    exit;
                }

                if ($action === 'reset_password') {
                    $targetId = (int) ($_POST['user_id'] ?? 0);
                    $hash = password_hash('123', PASSWORD_DEFAULT);
                    $q = $pdo->prepare(
                        'UPDATE users SET password_hash = :hash, must_change_password = 1,
                         password_changed_at = NULL, failed_login_attempts = 0, locked_until = NULL
                         WHERE id = :id AND role <> \'GEST\''
                    );
                    $q->execute(['hash' => $hash, 'id' => $targetId]);
                    header('Location: ?page=utilisateurs&success=1');
                    exit;
                }

                if ($action === 'toggle_user') {
                    $targetId = (int) ($_POST['user_id'] ?? 0);
                    if ($targetId === (int) $currentUser['id']) {
                        throw new InvalidArgumentException('Vous ne pouvez pas désactiver votre propre compte.');
                    }
                    $q = $pdo->prepare(
                        'UPDATE users SET is_active = IF(is_active = 1, 0, 1)
                         WHERE id = :id AND role <> \'GEST\''
                    );
                    $q->execute(['id' => $targetId]);
                    header('Location: ?page=utilisateurs&success=1');
                    exit;
                }

                if ($action === 'company_restriction') {
                    $targetId = (int) ($_POST['user_id'] ?? 0);
                    $companyId = (int) ($_POST['company_id'] ?? 0);
                    $mode = (string) ($_POST['mode'] ?? '');
                    $q = $pdo->prepare('SELECT role FROM users WHERE id = :id');
                    $q->execute(['id' => $targetId]);
                    $targetRole = $q->fetchColumn();
                    if (!$targetRole || $targetRole === 'GEST' || !in_array($mode, ['block', 'allow'], true)) {
                        throw new InvalidArgumentException('Sélection d’utilisateur ou d’entreprise invalide.');
                    }
                    if ($mode === 'block') {
                        $q = $pdo->prepare(
                            'INSERT IGNORE INTO user_company_restrictions (user_id, company_id, restricted_by)
                             VALUES (:uid, :cid, :admin)'
                        );
                        $q->execute(['uid' => $targetId, 'cid' => $companyId, 'admin' => (int) $currentUser['id']]);
                    } else {
                        $q = $pdo->prepare('DELETE FROM user_company_restrictions WHERE user_id = :uid AND company_id = :cid');
                        $q->execute(['uid' => $targetId, 'cid' => $companyId]);
                    }
                    header('Location: ?page=utilisateurs&success=1');
                    exit;
                }

                if ($action === 'permission_restriction') {
                    $targetId = (int) ($_POST['user_id'] ?? 0);
                    $companyId = (int) ($_POST['company_id'] ?? 0);
                    $permissionId = (int) ($_POST['permission_id'] ?? 0);
                    $mode = (string) ($_POST['mode'] ?? '');
                    $q = $pdo->prepare('SELECT role FROM users WHERE id = :id');
                    $q->execute(['id' => $targetId]);
                    $targetRole = $q->fetchColumn();
                    if (!$targetRole || $targetRole === 'GEST' || !in_array($mode, ['block', 'allow'], true)) {
                        throw new InvalidArgumentException('Sélection d’utilisateur ou de droit invalide.');
                    }
                    if ($mode === 'block') {
                        $q = $pdo->prepare(
                            'INSERT IGNORE INTO user_permission_restrictions
                             (user_id, company_id, permission_id, restricted_by)
                             VALUES (:uid, :cid, :pid, :admin)'
                        );
                        $q->execute([
                            'uid' => $targetId, 'cid' => $companyId, 'pid' => $permissionId,
                            'admin' => (int) $currentUser['id'],
                        ]);
                    } else {
                        $q = $pdo->prepare(
                            'DELETE FROM user_permission_restrictions
                             WHERE user_id = :uid AND company_id = :cid AND permission_id = :pid'
                        );
                        $q->execute(['uid' => $targetId, 'cid' => $companyId, 'pid' => $permissionId]);
                    }
                    header('Location: ?page=utilisateurs&success=1');
                    exit;
                }

                throw new InvalidArgumentException('Action non reconnue.');
            } catch (InvalidArgumentException $exception) {
                $errorMessage = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log('ERP Manager - gestion utilisateurs : ' . $exception->getMessage());
                $errorMessage = 'Impossible d’effectuer cette opération. Vérifiez que l’identifiant n’existe pas déjà et que les données sont valides.';
            }
        }
    }

    $users = $pdo->query(
        'SELECT id, username, display_name, role, is_active, must_change_password
         FROM users ORDER BY role, username'
    )->fetchAll();
    $companies = $pdo->query('SELECT id, name FROM companies ORDER BY name')->fetchAll();
    $permissions = $pdo->query('SELECT id, code, label FROM permissions ORDER BY label')->fetchAll();
    $companyRestrictions = $pdo->query(
        'SELECT r.id, u.username, c.name AS company_name
         FROM user_company_restrictions r
         INNER JOIN users u ON u.id = r.user_id
         INNER JOIN companies c ON c.id = r.company_id
         ORDER BY u.username, c.name'
    )->fetchAll();
    $permissionRestrictions = $pdo->query(
        'SELECT r.id, u.username, c.name AS company_name, p.label AS permission_label
         FROM user_permission_restrictions r
         INNER JOIN users u ON u.id = r.user_id
         INNER JOIN companies c ON c.id = r.company_id
         INNER JOIN permissions p ON p.id = r.permission_id
         ORDER BY u.username, c.name, p.label'
    )->fetchAll();

    require dirname(__DIR__) . '/resources/views/users.php';
    exit;
}

if ($page === 'mon_mot_de_passe') {
    $currentUser = $auth->requireLogin();

    if ($isPost) {
        if (!$auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
            $errorMessage = 'Votre session a expiré. Rechargez la page et réessayez.';
        } else {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmation = (string) ($_POST['confirm_password'] ?? '');

            if (strlen($newPassword) < 10) {
                $errorMessage = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
            } elseif ($newPassword !== $confirmation) {
                $errorMessage = 'La confirmation du nouveau mot de passe ne correspond pas.';
            } elseif ($newPassword === '123') {
                $errorMessage = 'Choisissez un mot de passe différent du mot de passe initial.';
            } elseif (!$auth->changePassword((int) $currentUser['id'], $currentPassword, $newPassword)) {
                $errorMessage = 'Le mot de passe actuel est incorrect.';
            } else {
                header('Location: ?page=dashboard&password_changed=1');
                exit;
            }
        }
    }

    require dirname(__DIR__) . '/resources/views/change-password.php';
    exit;
}


if ($page === 'marche') {
    $currentUser = $auth->requireLogin();
    $marketId = (int) ($_GET['id'] ?? $_POST['market_id'] ?? 0);
    $market = null;
    $qualifications = [];
    $history = [];
    $successMessage = isset($_GET['success']) ? 'Opération enregistrée avec succès.' : '';
    $errorMessage = '';
    $schemaReady = false;
    $lotsSchemaReady = false;
    $lots = [];
    $selectedLot = null;
    $selectedLotId = 0;

    try {
        $market = MarketLifecycle::findMarket($pdo, $marketId);
        if (!$market) {
            http_response_code(404);
            echo 'Marché introuvable.';
            exit;
        }
        $companyId = (int) $market['company_id'];
        $userId = (int) $currentUser['id'];
        $role = (string) $currentUser['role'];

        if (!$auth->hasCompanyAccess($userId, $role, $companyId)
            || !$auth->hasPermission($userId, $role, $companyId, 'markets.view')) {
            http_response_code(403);
            echo 'Accès refusé à ce marché.';
            exit;
        }

        $check = $pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME IN ('market_result_qualifications', 'market_result_validations')) AS table_count,
                (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND ((TABLE_NAME = 'markets' AND COLUMN_NAME = 'official_result_qualification_id')
                     OR (TABLE_NAME = 'market_transitions' AND COLUMN_NAME = 'qualification_id'))) AS column_count,
                (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND CONSTRAINT_NAME IN ('fk_market_transitions_qualification_market',
                                            'fk_markets_official_result_qualification_market')) AS constraint_count,
                (SELECT COUNT(*) FROM permissions
                 WHERE code IN ('markets.lifecycle.advance', 'markets.results.qualify',
                                'markets.results.validate', 'markets.lifecycle.reopen')) AS permission_count"
        );
        $schemaState = $check->fetch();
        $schemaReady = (int) ($schemaState['table_count'] ?? 0) === 2
            && (int) ($schemaState['column_count'] ?? 0) === 2
            && (int) ($schemaState['constraint_count'] ?? 0) === 2
            && (int) ($schemaState['permission_count'] ?? 0) === 4;

        $lotsSchemaReady = MarketLifecycle::lotsSchemaReady($pdo);
        if ($lotsSchemaReady) {
            $lots = MarketLifecycle::lots($pdo, $marketId);
            if (!$lots) {
                throw new RuntimeException('Aucun lot n’est associé à ce marché. Vérifiez la migration 005 avant de continuer.');
            }
            $requestedLotId = (int) ($_GET['lot_id'] ?? $_POST['lot_id'] ?? 0);
            foreach ($lots as $lotRow) {
                if ($requestedLotId === (int) $lotRow['id']) {
                    $selectedLot = $lotRow;
                    break;
                }
            }
            if ($selectedLot === null) $selectedLot = $lots[0];
            $selectedLotId = (int) $selectedLot['id'];
            // La vue conserve l'en-tête du marché, mais affiche le cycle du lot sélectionné.
            $market['lifecycle_status'] = $selectedLot['lifecycle_status'];
            $market['lifecycle_phase'] = $selectedLot['lifecycle_phase'];
            $market['status'] = $selectedLot['lifecycle_status'];
            $market['phase'] = $selectedLot['lifecycle_phase'];
        }

        if ($isPost) {
            if (!$auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
                throw new RuntimeException('Votre session a expiré. Rechargez la page et réessayez.');
            }

            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create_lot') {
                if (!$lotsSchemaReady) throw new RuntimeException('La migration 005 doit être installée avant la création de lots.');
                if (!$auth->hasPermission($userId, $role, $companyId, 'markets.create')) {
                    http_response_code(403);
                    throw new DomainException('Vous ne disposez pas du droit de créer un lot.');
                }
                $newLotId = MarketLifecycle::createLot(
                    $pdo, $marketId, (string) ($_POST['lot_label'] ?? ''),
                    (string) ($_POST['lot_reference'] ?? '')
                );
                header('Location: ?page=marche&id=' . $marketId . '&lot_id=' . $newLotId . '&success=1');
                exit;
            }
            if (!$schemaReady && ($action !== 'advance' || (int) $market['lifecycle_phase'] > 4)) {
                throw new RuntimeException('La migration 004 doit être installée avant les qualifications, validations et phases 6 à 8.');
            }
            if ($lotsSchemaReady && $selectedLotId < 1) {
                throw new RuntimeException('Sélectionnez un lot valide.');
            }
            $permission = match ($action) {
                'advance' => 'markets.lifecycle.advance',
                'qualify' => 'markets.results.qualify',
                'validate' => 'markets.results.validate',
                'reopen' => 'markets.lifecycle.reopen',
                default => '',
            };
            if ($permission === '' || !$auth->hasPermission($userId, $role, $companyId, $permission)) {
                http_response_code(403);
                throw new DomainException('Vous ne disposez pas du droit nécessaire pour cette action.');
            }

            if ($action === 'advance') {
                if ($lotsSchemaReady) {
                    MarketLifecycle::advanceLotPhase(
                        $pdo, $marketId, $selectedLotId, $userId,
                        (string) ($_POST['reason'] ?? ''),
                        isset($_POST['closure_confirmed']) && $_POST['closure_confirmed'] === '1'
                    );
                } else {
                    MarketLifecycle::advancePhase(
                        $pdo, $marketId, $userId,
                        (string) ($_POST['reason'] ?? ''),
                        isset($_POST['closure_confirmed']) && $_POST['closure_confirmed'] === '1'
                    );
                }
            } elseif ($action === 'qualify') {
                if ($lotsSchemaReady) MarketLifecycle::qualifyLotResult($pdo, $marketId, $selectedLotId, $userId, $_POST);
                else MarketLifecycle::qualifyResult($pdo, $marketId, $userId, $_POST);
            } elseif ($action === 'validate') {
                if ($lotsSchemaReady) {
                    MarketLifecycle::validateLotResult(
                        $pdo, $marketId, $selectedLotId, (int) ($_POST['qualification_id'] ?? 0), $userId,
                        (string) ($_POST['decision'] ?? ''), (string) ($_POST['validation_reason'] ?? '')
                    );
                } else {
                    MarketLifecycle::validateResult(
                        $pdo, $marketId, (int) ($_POST['qualification_id'] ?? 0), $userId,
                        (string) ($_POST['decision'] ?? ''), (string) ($_POST['validation_reason'] ?? '')
                    );
                }
            } elseif ($action === 'reopen') {
                if ($lotsSchemaReady) MarketLifecycle::reopenLot($pdo, $marketId, $selectedLotId, $userId, $role, (string) ($_POST['reason'] ?? ''));
                else MarketLifecycle::reopen($pdo, $marketId, $userId, $role, (string) ($_POST['reason'] ?? ''));
            }
            header('Location: ?page=marche&id=' . $marketId . ($lotsSchemaReady ? '&lot_id=' . $selectedLotId : '') . '&success=1');
            exit;
        }

        if ($schemaReady) {
            $qualifications = MarketLifecycle::qualifications($pdo, $marketId, $lotsSchemaReady ? $selectedLotId : null);
        }
        $history = MarketLifecycle::history($pdo, $marketId, $schemaReady, $lotsSchemaReady ? $selectedLotId : null);
    } catch (PDOException $exception) {
        error_log('ERP Manager - fiche marché : ' . $exception->getMessage());
        $errorMessage = 'Impossible de charger ou de traiter cette fiche. Vérifiez que les migrations requises sont installées.';
    } catch (InvalidArgumentException | DomainException | RuntimeException $exception) {
        $errorMessage = $exception->getMessage();
    }

    $pageTitle = 'Fiche du marché';
    $csrfToken = $auth->csrfToken();
    require dirname(__DIR__) . '/resources/views/market-detail.php';
    exit;
}

if ($page === 'marches' || $page === 'marche_nouveau') {
    $currentUser = $auth->requireLogin();
    $pageTitle = $page === 'marche_nouveau' ? 'Nouveau marché' : 'Gestion des marchés';
    $companies = [];
    $allowedCompanyIds = [];
    $canCreateMarket = false;
    $markets = [];
    $successMessage = '';
    $errorMessage = '';

    try {
        $companyRows = $pdo->query('SELECT id, name FROM companies ORDER BY name')->fetchAll();
        foreach ($companyRows as $company) {
            $companyId = (int) $company['id'];
            if (!$auth->hasCompanyAccess((int) $currentUser['id'], (string) $currentUser['role'], $companyId)
                || !$auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], $companyId, 'markets.view')) {
                continue;
            }
            $allowedCompanyIds[] = $companyId;
            $companies[] = ['id' => $companyId, 'name' => (string) $company['name']];
            if ($auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], $companyId, 'markets.create')) {
                $canCreateMarket = true;
            }
        }

        if ($page === 'marche_nouveau') {
            if (!$canCreateMarket) {
                http_response_code(403);
                echo 'Vous ne disposez pas du droit de créer un marché.';
                exit;
            }

            if ($isPost) {
                if (!$auth->verifyCsrf($_POST['csrf_token'] ?? null)) {
                    $errorMessage = 'Votre session a expiré. Rechargez la page et réessayez.';
                } else {
                    $reference = trim((string) ($_POST['reference'] ?? ''));
                    $object = trim((string) ($_POST['object'] ?? ''));
                    $companyId = (int) ($_POST['company_id'] ?? 0);
                    $amountRaw = str_replace(',', '.', trim((string) ($_POST['amount'] ?? '0')));
                    $marketDate = (string) ($_POST['market_date'] ?? '');
                    $validDate = DateTime::createFromFormat('!Y-m-d', $marketDate);
                    if ($reference === '' || strlen($reference) > 40 || $object === '' || strlen($object) > 10000
                        || !in_array($companyId, $allowedCompanyIds, true)
                        || !$auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], $companyId, 'markets.create')
                        || !is_numeric($amountRaw) || (float) $amountRaw < 0 || !preg_match('/^\d{1,13}(\.\d{1,2})?$/', $amountRaw)
                        || !$validDate || $validDate->format('Y-m-d') !== $marketDate) {
                        $errorMessage = 'Vérifiez les champs obligatoires et les valeurs saisies.';
                    } else {
                        MarketLifecycle::createInitial($pdo, [
                            'reference' => $reference,
                            'object' => $object,
                            'company_id' => $companyId,
                            'amount' => $amountRaw,
                            'market_date' => $marketDate,
                        ], (int) $currentUser['id']);
                        header('Location: ?page=marches&created=1');
                        exit;
                    }
                }
            }
        } else {
            $successMessage = isset($_GET['created']) ? 'Le marché a été ajouté avec succès.' : '';
            if ($allowedCompanyIds) {
                $where = ['m.company_id IN (' . implode(',', array_fill(0, count($allowedCompanyIds), '?')) . ')'];
                $params = $allowedCompanyIds;
                $search = trim((string) ($_GET['q'] ?? ''));
                $statusFilter = (string) ($_GET['status'] ?? '');
                $companyFilter = (int) ($_GET['company'] ?? 0);

                if ($search !== '') {
                    $where[] = '(m.reference LIKE ? OR m.object LIKE ? OR c.name LIKE ?)';
                    $like = '%' . $search . '%';
                    array_push($params, $like, $like, $like);
                }
                $lotsSchemaReady = MarketLifecycle::lotsSchemaReady($pdo);
                if (in_array($statusFilter, MarketLifecycle::statusCodes(), true)) {
                    if ($lotsSchemaReady) {
                        $where[] = 'EXISTS (SELECT 1 FROM market_lots lf WHERE lf.market_id = m.id AND lf.lifecycle_status = ?)';
                    } else {
                        $where[] = 'm.lifecycle_status = ?';
                    }
                    $params[] = $statusFilter;
                }
                if ($companyFilter > 0 && in_array($companyFilter, $allowedCompanyIds, true)) {
                    $where[] = 'm.company_id = ?';
                    $params[] = $companyFilter;
                }

                if ($lotsSchemaReady) {
                    $sql = 'SELECT m.id, m.reference, m.object, m.company_id, c.name AS company_name,
                                   CASE WHEN COUNT(l.id) = 1 THEN MAX(l.lifecycle_status) ELSE "multi_lots" END AS status,
                                   CASE WHEN COUNT(l.id) = 1 THEN MAX(l.lifecycle_phase) ELSE 0 END AS phase,
                                   COUNT(l.id) AS lot_count,
                                   m.amount, m.market_date, m.is_demo
                            FROM markets m INNER JOIN companies c ON c.id = m.company_id
                            LEFT JOIN market_lots l ON l.market_id = m.id
                            WHERE ' . implode(' AND ', $where) . '
                            GROUP BY m.id, m.reference, m.object, m.company_id, c.name,
                                     m.amount, m.market_date, m.is_demo
                            ORDER BY m.market_date DESC, m.id DESC';
                } else {
                    $sql = 'SELECT m.id, m.reference, m.object, m.company_id, c.name AS company_name,
                                   m.lifecycle_status AS status, m.lifecycle_phase AS phase, 1 AS lot_count,
                                   m.amount, m.market_date, m.is_demo
                            FROM markets m INNER JOIN companies c ON c.id = m.company_id
                            WHERE ' . implode(' AND ', $where) . '
                            ORDER BY m.market_date DESC, m.id DESC';
                }
                $query = $pdo->prepare($sql);
                $query->execute($params);
                $markets = $query->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        error_log('ERP Manager - module marchés : ' . $exception->getMessage());
        $errorMessage = 'Une erreur est survenue lors du chargement ou de l’enregistrement.';
    }

    $csrfToken = $auth->csrfToken();
    require dirname(__DIR__) . '/resources/views/markets.php';
    exit;
}

$currentUser = $auth->requireLogin();

$pageTitle = 'Tableau de bord';
$currentDate = date('d/m/Y');
$currentTime = date('H:i');
$dashboardData = ['companies' => [], 'markets' => []];
$databaseError = false;

try {
    $companyRows = $pdo->query(
        'SELECT id, code, name, short_name, color, is_demo FROM companies ORDER BY id ASC'
    )->fetchAll();

    $allowedCompanyIds = [];
    foreach ($companyRows as $company) {
        $companyId = (int) $company['id'];
        if (!$auth->hasCompanyAccess((int) $currentUser['id'], (string) $currentUser['role'], $companyId)) {
            continue;
        }
        if (!$auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], $companyId, 'dashboard.view')) {
            continue;
        }
        if (!$auth->hasPermission((int) $currentUser['id'], (string) $currentUser['role'], $companyId, 'companies.view')) {
            continue;
        }
        if (!preg_match('/^[A-Z0-9_]{1,50}$/', (string) $company['code'])) {
            continue;
        }

        $allowedCompanyIds[] = $companyId;
        $dashboardData['companies'][] = [
            'id' => (string) $company['code'],
            'name' => (string) $company['name'],
            'short' => (string) $company['short_name'],
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $company['color'])
                ? (string) $company['color'] : '#64748b',
            'isDemo' => (bool) $company['is_demo'],
        ];
    }

    $marketCompanyIds = array_values(array_filter(
        $allowedCompanyIds,
        fn (int $companyId): bool => $auth->hasPermission(
            (int) $currentUser['id'],
            (string) $currentUser['role'],
            $companyId,
            'markets.view'
        )
    ));

    if ($marketCompanyIds) {
        $placeholders = implode(',', array_fill(0, count($marketCompanyIds), '?'));
        if (MarketLifecycle::lotsSchemaReady($pdo)) {
            $marketQuery = $pdo->prepare(
                'SELECT m.reference, m.object, m.company_id, c.code AS company_code,
                        CASE WHEN COUNT(l.id) = 1 THEN MAX(l.lifecycle_status) ELSE "multi_lots" END AS status,
                        CASE WHEN COUNT(l.id) = 1 THEN MAX(l.lifecycle_phase) ELSE 0 END AS phase,
                        m.amount, m.market_date, m.is_demo
                 FROM markets m
                 INNER JOIN companies c ON c.id = m.company_id
                 LEFT JOIN market_lots l ON l.market_id = m.id
                 WHERE m.company_id IN (' . $placeholders . ')
                 GROUP BY m.id, m.reference, m.object, m.company_id, c.code,
                          m.amount, m.market_date, m.is_demo
                 ORDER BY m.market_date DESC, m.id DESC'
            );
        } else {
            $marketQuery = $pdo->prepare(
                'SELECT m.reference, m.object, m.company_id, c.code AS company_code,
                        m.lifecycle_status AS status, m.lifecycle_phase AS phase,
                        m.amount, m.market_date, m.is_demo
                 FROM markets m
                 INNER JOIN companies c ON c.id = m.company_id
                 WHERE m.company_id IN (' . $placeholders . ')
                 ORDER BY m.market_date DESC, m.id DESC'
            );
        }
        $marketQuery->execute($marketCompanyIds);
        $marketRows = $marketQuery->fetchAll();

        foreach ($marketRows as $market) {
            $companyId = (int) $market['company_id'];
            if (!$auth->hasPermission(
                (int) $currentUser['id'],
                (string) $currentUser['role'],
                $companyId,
                'markets.view'
            )) {
                continue;
            }
            $dashboardData['markets'][] = [
                'numero' => (string) $market['reference'],
                'objet' => (string) $market['object'],
                'companyId' => (string) $market['company_code'],
                'status' => (string) $market['status'],
                'phase' => (int) $market['phase'],
                'amount' => (float) $market['amount'],
                'date' => date('d/m/Y', strtotime((string) $market['market_date'])),
                'isDemo' => (bool) $market['is_demo'],
            ];
        }
    }
} catch (Throwable $exception) {
    error_log('ERP Manager - chargement MySQL : ' . $exception->getMessage());
    $databaseError = true;
}

require dirname(__DIR__) . '/resources/views/dashboard.php';

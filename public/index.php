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
        $marketQuery = $pdo->prepare(
            'SELECT m.reference, m.object, m.company_id, c.code AS company_code,
                    m.status, m.phase, m.amount, m.market_date, m.is_demo
             FROM markets m
             INNER JOIN companies c ON c.id = m.company_id
             WHERE m.company_id IN (' . $placeholders . ')
             ORDER BY m.market_date DESC, m.id DESC'
        );
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

<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Casablanca');

$pageTitle = 'Tableau de bord';
$currentDate = date('d/m/Y');
$currentTime = date('H:i');

$dashboardData = ['companies' => [], 'markets' => []];
$databaseError = false;

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';

    $companyRows = $pdo->query(
        'SELECT code, name, short_name, color, is_demo FROM companies ORDER BY id ASC'
    )->fetchAll();

    $marketRows = $pdo->query(
        'SELECT m.reference, m.object, c.code AS company_code, m.status, m.phase,
                m.amount, m.market_date, m.is_demo
         FROM markets m
         INNER JOIN companies c ON c.id = m.company_id
         ORDER BY m.market_date DESC, m.id DESC'
    )->fetchAll();

    foreach ($companyRows as $company) {
        if (!preg_match('/^[A-Z0-9_]{1,50}$/', (string) $company['code'])) {
            continue;
        }
        $dashboardData['companies'][] = [
            'id' => (string) $company['code'],
            'name' => (string) $company['name'],
            'short' => (string) $company['short_name'],
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $company['color'])
                ? (string) $company['color'] : '#64748b',
            'isDemo' => (bool) $company['is_demo'],
        ];
    }

    foreach ($marketRows as $market) {
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
} catch (Throwable $exception) {
    error_log('ERP Manager - chargement MySQL : ' . $exception->getMessage());
    $databaseError = true;
}

require dirname(__DIR__) . '/resources/views/dashboard.php';

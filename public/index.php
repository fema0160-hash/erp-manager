<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Casablanca');

$pageTitle = 'Tableau de bord';
$currentDate = date('d/m/Y');
$currentTime = date('H:i');

require dirname(__DIR__) . '/resources/views/dashboard.php';

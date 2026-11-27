<?php
declare(strict_types=1);

/** Connexion PDO. Les paramètres locaux restent hors de Git. */
$localPath = __DIR__ . '/database.local.php';
$local = is_file($localPath) ? require $localPath : [];
if (!is_array($local)) {
    throw new RuntimeException('Configuration locale MySQL invalide.');
}

$host = (string) ($local['host'] ?? getenv('DB_HOST') ?: '127.0.0.1');
$port = (string) ($local['port'] ?? getenv('DB_PORT') ?: '3306');
$name = (string) ($local['name'] ?? getenv('DB_NAME') ?: 'erp_manager');
$user = (string) ($local['user'] ?? getenv('DB_USER') ?: 'root');
$pass = (string) ($local['pass'] ?? getenv('DB_PASS') ?: '');

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
return new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

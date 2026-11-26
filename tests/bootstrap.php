<?php
// tests/bootstrap.php
declare(strict_types=1);

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/bootstrap.php';

$prodDb = (string)($_ENV['DB_NAME'] ?? '');
$testDb = trim((string)(getenv('DB_TEST_NAME') ?: ($_ENV['DB_TEST_NAME'] ?? 'nokia1100_test')));

if ($testDb === '' || $testDb === $prodDb || !str_ends_with($testDb, '_test')) {
    fwrite(STDERR, "Abortado: DB_TEST_NAME inválido ('{$testDb}'). Debe terminar en '_test' y ser distinto de DB_NAME ('{$prodDb}').\n");
    exit(1);
}

$_ENV['DB_NAME'] = $testDb;
$_SERVER['DB_NAME'] = $testDb;
putenv("DB_NAME={$testDb}");

$testUser = trim((string)(getenv('DB_TEST_USER') ?: ''));
if ($testUser !== '') {
    $testPass = (string)(getenv('DB_TEST_PASS') !== false ? getenv('DB_TEST_PASS') : '');
    $_ENV['DB_USER'] = $testUser;
    $_ENV['DB_PASS'] = $testPass;
    $_SERVER['DB_USER'] = $testUser;
    $_SERVER['DB_PASS'] = $testPass;
}

$_ENV['MAIL_DISABLED'] = 'true';
$_ENV['NOTIFY_CLIENT_EMAILS'] = 'false';
$_SERVER['MAIL_DISABLED'] = 'true';
$_SERVER['NOTIFY_CLIENT_EMAILS'] = 'false';

mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

$conn = new mysqli(
    (string)($_ENV['DB_HOST'] ?? 'localhost'),
    (string)($_ENV['DB_USER'] ?? 'root'),
    (string)($_ENV['DB_PASS'] ?? '')
);
$conn->set_charset('utf8mb4');
$conn->query("CREATE DATABASE IF NOT EXISTS `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($testDb);
$conn->query("SET time_zone = '-03:00'");

$resTables = $conn->query("SHOW TABLES LIKE 'usuario'");
$hasSchema = $resTables && $resTables->num_rows > 0;
if ($resTables) {
    $resTables->free();
}

if (!$hasSchema) {
    $dumpFile = $rootDir . '/database/nokia1100_actual.sql';
    if (!is_file($dumpFile)) {
        fwrite(STDERR, "No se encontró el volcado base en {$dumpFile}\n");
        exit(1);
    }
    $sql = file_get_contents($dumpFile);
    if ($sql === false) {
        fwrite(STDERR, "No se pudo leer {$dumpFile}\n");
        exit(1);
    }
    $conn->query('SET FOREIGN_KEY_CHECKS=0');
    if ($conn->multi_query($sql)) {
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
}

require_once $rootDir . '/database/run_migrations.php';
apply_system_migrations($conn, true);

$conn->close();

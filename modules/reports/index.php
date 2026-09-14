<?php
// modules/reports/index.php
require_once __DIR__ . '/Controllers/ReportsController.php';
$controller = new ReportsController();

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
    default:
        $controller->dashboard();
        break;
}
?>
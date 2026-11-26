<?php
// modules/reports/index.php
require_once __DIR__ . '/Controllers/ReportsController.php';
$controller = new ReportsController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        $controller->dashboard();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}
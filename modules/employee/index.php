<?php
// modules/employee/index.php
require_once __DIR__ . '/Controllers/EmployeeController.php';
$controller = new EmployeeController();

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
    default:
        $controller->dashboard();
        break;
}
?>

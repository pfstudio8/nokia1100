<?php
// modules/employee/index.php
require_once __DIR__ . '/Controllers/EmployeeController.php';
$controller = new EmployeeController();

$action = $_POST['action'] ?? ($_GET['action'] ?? 'dashboard');

switch ($action) {
    case 'dashboard':
        $controller->dashboard();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

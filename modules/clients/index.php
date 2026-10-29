<?php
// modules/clients/index.php
require_once __DIR__ . '/Controllers/ClientsController.php';
$controller = new ClientsController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

switch ($action) {
    case 'edit':
        $controller->edit();
        break;
    case 'add':
    case 'index':
        $controller->index();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

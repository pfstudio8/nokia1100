<?php
// modules/clients/index.php
require_once __DIR__ . '/Controllers/ClientsController.php';
$controller = new ClientsController();

$action = $_POST['action'] ?? ($_GET['action'] ?? 'index');

switch ($action) {
    case 'edit':
        $controller->edit();
        break;
    case 'index':
        $controller->index();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

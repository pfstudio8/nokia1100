<?php
// modules/workshop/index.php
require_once __DIR__ . '/Controllers/WorkshopController.php';
$controller = new WorkshopController();

$action = $_POST['action'] ?? ($_GET['action'] ?? 'index');

switch ($action) {
    case 'add':
        $controller->add();
        break;
    case 'view':
        $controller->view();
        break;
    case 'print_receipt':
        $controller->print_receipt();
        break;
    case 'delivery_receipt':
        $controller->delivery_receipt();
        break;
    case 'track':
        $controller->track();
        break;
    case 'history':
        $controller->history();
        break;
    case 'check_status':
        $controller->check_status();
        break;
    case 'index':
        $controller->index();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

<?php
// modules/workshop/index.php
require_once __DIR__ . '/Controllers/WorkshopController.php';
$controller = new WorkshopController();

$action = $_GET['action'] ?? 'index';

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
    case 'track':
        $controller->track();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
?>

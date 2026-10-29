<?php
// modules/inventory/index.php
require_once __DIR__ . '/Controllers/InventoryController.php';
$controller = new InventoryController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'inventory');

switch ($action) {
    case 'add_product':
        $controller->add_product();
        break;
    case 'process_product':
        $controller->process_product();
        break;
    case 'add_stock':
        $controller->add_stock();
        break;
    case 'edit_stock':
        $controller->edit_stock();
        break;
    case 'change_status':
        $controller->change_status();
        break;
    case 'delete_product':
        $controller->delete_product();
        break;
    case 'check_stock':
        $controller->check_stock();
        break;
    case 'ajax_create_product':
        $controller->ajax_create_product();
        break;
    case 'ajax_save_product':
        $controller->ajax_save_product();
        break;
    case 'get_purchase_history':
        $controller->get_purchase_history();
        break;
    case 'update_images':
        $controller->update_images();
        break;
    case 'inventory':
        $controller->inventory();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

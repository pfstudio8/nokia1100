<?php
// modules/inventory/index.php
require_once __DIR__ . '/Controllers/InventoryController.php';
$controller = new InventoryController();

$action = $_GET['action'] ?? 'inventory';

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
    case 'delete_product':
        $controller->delete_product();
        break;

    case 'inventory':
    default:
        $controller->inventory();
        break;
}
?>

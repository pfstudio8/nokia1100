<?php
// modules/inventory/index.php
require_once __DIR__ . '/Controllers/InventoryController.php';
$controller = new InventoryController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'inventory');

switch ($action) {
    case 'change_status':
        $controller->change_status();
        break;
    case 'check_stock':
        $controller->check_stock();
        break;
    case 'ajax_save_product':
        $controller->ajax_save_product();
        break;
    case 'inventory':
        $controller->inventory();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

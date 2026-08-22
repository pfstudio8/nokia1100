<?php
// modules/suppliers/index.php
require_once __DIR__ . '/Controllers/SuppliersController.php';
$controller = new SuppliersController();

$action = $_GET['action'] ?? 'suppliers';

switch ($action) {
    case 'edit_supplier':
        $controller->edit_supplier();
        break;
    case 'new_purchase':
        $controller->new_purchase();
        break;
    case 'purchase_history':
        $controller->purchase_history();
        break;
    case 'suppliers':
    default:
        $controller->suppliers();
        break;
}
?>

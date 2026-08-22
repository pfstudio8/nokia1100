<?php
// modules/sales/index.php
require_once __DIR__ . '/Controllers/SalesController.php';
$controller = new SalesController();

$action = $_GET['action'] ?? 'sales';

switch ($action) {
    case 'new_sale':
        $controller->new_sale();
        break;
    case 'invoice':
        $controller->invoice();
        break;
    case 'sales_charts':
        $controller->sales_charts();
        break;
    case 'export_sales':
        $controller->export_sales();
        break;
    case 'sales':
    default:
        $controller->sales();
        break;
}
?>

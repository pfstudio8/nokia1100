<?php
// modules/sales/index.php
require_once __DIR__ . '/Controllers/SalesController.php';
$controller = new SalesController();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'sales');

switch ($action) {
    case 'new_sale':
        $controller->new_sale();
        break;
    case 'repairs_to_charge':
    case 'charge_repair':
        $controller->repairs_to_charge();
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
    case 'check_ready_repairs':
        $controller->check_ready_repairs();
        break;
    case 'credit_note':
        $controller->credit_note();
        break;
    case 'rollback':
        $controller->rollback();
        break;
    case 'sales':
        $controller->sales();
        break;
    default:
        http_response_code(404);
        exit('Acción no encontrada');
}

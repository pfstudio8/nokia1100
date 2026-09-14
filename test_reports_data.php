<?php
define('BASE_URL', 'http://localhost/nokia1100');
require_once 'database/database.php';
require_once 'modules/admin/Models/AdminModel.php';
require_once 'modules/inventory/Models/InventoryModel.php';
require_once 'modules/sales/Models/SalesModel.php';

$admin_model = new AdminModel();
$inventory_model = new InventoryModel();
$sales_model = new SalesModel();

$financial = $admin_model->get_financial_stats();
$stock_stats = $inventory_model->get_stock_stats();
$top_value = $inventory_model->get_top_value_products(5);
$sales_daily = $sales_model->get_sales_daily_totals();
$sales_methods = $sales_model->get_sales_by_payment_methods();
$top_sales = $sales_model->get_top_products(5);

?>
<script>
    window.reportsData = {
        financial: <?php echo json_encode($financial ?? []); ?>,
        stock: <?php echo json_encode($stock_stats ?? []); ?>,
        topValue: <?php echo json_encode($top_value ?? []); ?>,
        salesDaily: <?php echo json_encode($sales_daily ?? []); ?>,
        salesMethods: <?php echo json_encode($sales_methods ?? []); ?>,
        topSales: <?php echo json_encode($top_sales ?? []); ?>
    };
</script>

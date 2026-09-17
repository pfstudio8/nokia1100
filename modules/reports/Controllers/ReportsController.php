<?php
// modules/reports/Controllers/ReportsController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../../admin/Models/AdminModel.php';
require_once __DIR__ . '/../../inventory/Models/InventoryModel.php';
require_once __DIR__ . '/../../sales/Models/SalesModel.php';
require_once __DIR__ . '/../../workshop/Models/WorkshopModel.php';

class ReportsController extends BaseController
{
    private $admin_model;
    private $inventory_model;
    private $sales_model;
    private $workshop_model;

    public function __construct()
    {
        parent::__construct();
        $this->admin_model = new AdminModel();
        $this->inventory_model = new InventoryModel();
        $this->sales_model = new SalesModel();
        $this->workshop_model = new WorkshopModel();
    }

    public function dashboard()
    {
        $this->check_auth();
        // Permiso especial 'reportes' o usar 'graficos' que ya existía
        $this->check_access('reportes');

        // Data 1: Financiero
        $financial_stats = $this->admin_model->get_financial_stats();
        $revenue_category = $this->sales_model->get_revenue_by_category();
        $top_profitable = $this->sales_model->get_top_profitable_products(5);

        // Data 2: Inventario
        $stock_stats = $this->inventory_model->get_stock_stats();
        $top_value = $this->inventory_model->get_top_value_products(5);
        $stock_brand = $this->inventory_model->get_stock_by_brand();
        $critical_stock = $this->inventory_model->get_critical_stock_details();

        // Data 3: Ventas
        $sales_daily = $this->sales_model->get_sales_daily_totals();
        $sales_methods = $this->sales_model->get_sales_by_payment_methods();
        $top_sales = $this->sales_model->get_top_products(5);
        $sales_category = $this->sales_model->get_sales_by_category();
        
        // Data 4: Taller / Reparaciones
        $repairs = $this->workshop_model->get_all_repairs_for_report();
        $repair_status = $this->workshop_model->get_repair_status_stats();
        $repair_brand = $this->workshop_model->get_repairs_by_brand();

        $this->render_view(__DIR__ . '/../Views/reports.php', [
            'financial' => $financial_stats,
            'revenue_category' => $revenue_category,
            'top_profitable' => $top_profitable,
            'stock_stats' => $stock_stats,
            'top_value' => $top_value,
            'stock_brand' => $stock_brand,
            'critical_stock' => $critical_stock,
            'sales_daily' => $sales_daily,
            'sales_methods' => $sales_methods,
            'top_sales' => $top_sales,
            'sales_category' => $sales_category,
            'repairs' => $repairs,
            'repair_status' => $repair_status,
            'repair_brand' => $repair_brand
        ]);
    }
}
?>

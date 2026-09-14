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

        // Data 2: Inventario
        $stock_stats = $this->inventory_model->get_stock_stats();
        $top_value = $this->inventory_model->get_top_value_products(5);
        $critical_stock = $this->inventory_model->get_critical_stock_details();

        // Data 3: Ventas
        $sales_daily = $this->sales_model->get_sales_daily_totals();
        $sales_methods = $this->sales_model->get_sales_by_payment_methods();
        $top_sales = $this->sales_model->get_top_products(5);
        
        // Data 4: Taller / Reparaciones
        $repairs = $this->workshop_model->get_all_repairs_for_report();

        $this->render_view(__DIR__ . '/../Views/reports.php', [
            'financial' => $financial_stats,
            'stock_stats' => $stock_stats,
            'top_value' => $top_value,
            'critical_stock' => $critical_stock,
            'sales_daily' => $sales_daily,
            'sales_methods' => $sales_methods,
            'top_sales' => $top_sales,
            'repairs' => $repairs
        ]);
    }
}
?>
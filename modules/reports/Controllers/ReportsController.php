<?php
// modules/reports/Controllers/ReportsController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../../admin/Models/AdminModel.php';
require_once __DIR__ . '/../../inventory/Models/InventoryModel.php';
require_once __DIR__ . '/../../sales/Models/SalesModel.php';
require_once __DIR__ . '/../../workshop/Models/WorkshopModel.php';

class ReportsController extends BaseController
{
    private AdminModel $admin_model;
    private InventoryModel $inventory_model;
    private SalesModel $sales_model;
    private WorkshopModel $workshop_model;

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
        $this->check_access('reportes');

        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $hora_desde = trim((string)($_GET['hora_desde'] ?? '00:00'));
        $hora_hasta = trim((string)($_GET['hora_hasta'] ?? '23:59'));

        if ($desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = '';
        }
        if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = '';
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora_desde)) {
            $hora_desde = '00:00';
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora_hasta)) {
            $hora_hasta = '23:59';
        }

        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        if ($hora_desde > $hora_hasta) {
            [$hora_desde, $hora_hasta] = [$hora_hasta, $hora_desde];
        }

        $start_dt = ($desde !== '') ? ($desde . ' ' . $hora_desde . ':00') : null;
        $end_dt = ($hasta !== '') ? ($hasta . ' ' . $hora_hasta . ':59') : null;

        $allowedTabs = ['tab-finanzas', 'tab-inventario', 'tab-ventas', 'tab-taller'];
        $active_tab = trim((string)($_GET['tab'] ?? 'tab-finanzas'));
        if (!in_array($active_tab, $allowedTabs, true)) {
            $active_tab = 'tab-finanzas';
        }

        // Data 1: Financiero
        $financial_stats = $this->admin_model->get_financial_stats($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $revenue_category = $this->sales_model->get_revenue_by_category($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $top_profitable = $this->sales_model->get_top_profitable_products(5, $start_dt, $end_dt, $hora_desde, $hora_hasta);

        // Data 2: Inventario
        $stock_stats = $this->inventory_model->get_stock_stats($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $top_value = $this->inventory_model->get_top_value_products(5, $start_dt, $end_dt, $hora_desde, $hora_hasta);
        $stock_brand = $this->inventory_model->get_stock_by_brand($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $critical_stock = $this->inventory_model->get_critical_stock_details($start_dt, $end_dt, $hora_desde, $hora_hasta);

        // Data 3: Ventas
        $sales_daily = $this->sales_model->get_sales_daily_totals($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $sales_methods = $this->sales_model->get_sales_by_payment_methods($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $top_sales = $this->sales_model->get_top_products(5, $start_dt, $end_dt, $hora_desde, $hora_hasta);
        $sales_category = $this->sales_model->get_sales_by_category($start_dt, $end_dt, $hora_desde, $hora_hasta);
        
        // Data 4: Taller / Reparaciones
        $repairs = $this->workshop_model->get_all_repairs_for_report($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $repair_status = $this->workshop_model->get_repair_status_stats($start_dt, $end_dt, $hora_desde, $hora_hasta);
        $repair_brand = $this->workshop_model->get_repairs_by_brand($start_dt, $end_dt, $hora_desde, $hora_hasta);

        $this->render_view(__DIR__ . '/../Views/reports.php', [
            'desde' => $desde,
            'hasta' => $hasta,
            'hora_desde' => $hora_desde,
            'hora_hasta' => $hora_hasta,
            'active_tab' => $active_tab,
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

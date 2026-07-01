<?php
// modules/sales/Controllers/SalesController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/SalesModel.php';

class SalesController extends BaseController
{
    private $sales_model;

    public function __construct()
    {
        parent::__construct();
        $this->sales_model = new SalesModel();
    }

    public function sales()
    {
        $this->check_access('ventas');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        $sales = $this->sales_model->get_sales();

        $this->render_view(__DIR__ . '/../Views/list.php', [
            'sales' => $sales
        ]);
    }

    public function new_sale()
    {
        $this->check_auth();
        $this->check_access('venta');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);

            if ($input) {
                $items = $input['items'] ?? [];
                $metodo_pago = $input['metodo_pago'] ?? '';

                if (empty($items) || empty($metodo_pago)) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                    exit;
                }

                try {
                    $id_venta = $this->sales_model->create_sale_transaction($items, $metodo_pago, $_SESSION['user_id']);
                    echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito', 'id_venta' => $id_venta]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            }
        }

        $products = $this->sales_model->get_active_products_in_stock();

        $this->render_view(__DIR__ . '/../Views/new_sale.php', [
            'products' => $products
        ]);
    }

    public function invoice()
    {
        $this->check_auth();

        $id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id_venta === 0) {
            die("ID de venta no especificado.");
        }

        $venta = $this->sales_model->find_sale_by_id($id_venta);
        if (!$venta) {
            die("Venta no encontrada.");
        }

        $detalles = $this->sales_model->get_sale_details($id_venta);

        $this->render_view(__DIR__ . '/../Views/invoice.php', [
            'venta' => $venta,
            'detalles' => $detalles
        ]);
    }

    public function sales_charts()
    {
        $this->check_access('graficos');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        $daily = $this->sales_model->get_sales_daily_totals();
        $methods = $this->sales_model->get_sales_by_payment_methods();

        $this->render_view(__DIR__ . '/../Views/sales_charts.php', [
            'dates' => $daily['dates'],
            'totals' => $daily['totals'],
            'methods' => $methods['methods'],
            'method_amounts' => $methods['amounts']
        ]);
    }

    public function export_sales()
    {
        $this->check_auth();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ventas_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for right encoding in Excel
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['ID Venta', 'Fecha', 'Total ($)', 'Metodo de Pago', 'Producto', 'Cantidad']);

        $data = $this->sales_model->get_sales_export_data();

        foreach ($data as $row) {
            fputcsv($output, [
                $row['id_venta'],
                $row['fecha'],
                number_format($row['total'], 2, '.', ''),
                $row['metodo_de_pago'],
                $row['producto'] ? $row['producto'] : 'Desconocido',
                $row['cantidad'] ? $row['cantidad'] : 1
            ]);
        }
        fclose($output);
        exit;
    }
}
?>

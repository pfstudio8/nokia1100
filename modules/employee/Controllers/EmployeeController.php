<?php
// modules/employee/Controllers/EmployeeController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/EmployeeModel.php';

class EmployeeController extends BaseController
{
    private $employee_model;

    public function __construct()
    {
        parent::__construct();
        $this->employee_model = new EmployeeModel();
    }

    public function dashboard()
    {
        // Requiere rol 'empleado', 'admin' o 'guest'
        // Verifica la autenticación
        $this->check_auth();
        if ($_SESSION['role'] !== 'empleado' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'guest') {
            $this->redirect(BASE_URL . "/index.php");
        }

        $hoy = date('Y-m-d');
        
        if ($_SESSION['role'] === 'guest') {
            $total_hoy = 0.0;
            $trans_hoy = 0;
            $ventas_recientes = [];
        } else {
            $stats = $this->employee_model->get_daily_sales_stats($hoy);
            $total_hoy = $stats['total_hoy'];
            $trans_hoy = $stats['transacciones'];
            $ventas_recientes = $this->employee_model->get_recent_sales(5);
        }
        
        $productos = $this->employee_model->get_featured_products(4);

        $this->render_view(__DIR__ . '/../Views/dashboard.php', [
            'total_hoy' => $total_hoy,
            'trans_hoy' => $trans_hoy,
            'ventas_recientes' => $ventas_recientes,
            'productos' => $productos
        ]);
    }
}
?>

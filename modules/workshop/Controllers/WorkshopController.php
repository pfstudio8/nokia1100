<?php
// modules/workshop/Controllers/WorkshopController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/WorkshopModel.php';

class WorkshopController extends BaseController
{
    private $workshop_model;

    public function __construct()
    {
        // Don't require login inside construct, because track() is a public action!
        // We will call parent::__construct() though, which starts session.
        parent::__construct();
        $this->workshop_model = new WorkshopModel();
    }

    public function index()
    {
        $this->check_auth();
        $this->check_access('taller');

        $estado_filter = $_GET['estado'] ?? '';
        $search = $_GET['search'] ?? '';

        $repairs = $this->workshop_model->get_repairs($estado_filter, $search);

        $this->render_view(__DIR__ . '/../Views/index.php', [
            'repairs' => $repairs,
            'estado_filter' => $estado_filter,
            'search' => $search
        ]);
    }

    public function add()
    {
        $this->check_auth();
        $this->check_access('taller');

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id_reparacion = $this->workshop_model->create_repair_order(
                    $_POST['cliente_nombre'] ?? '',
                    $_POST['cliente_telefono'] ?? '',
                    '', // email
                    $_POST['equipo_marca'] ?? '',
                    $_POST['equipo_modelo'] ?? '',
                    $_POST['equipo_imei'] ?? '',
                    $_POST['falla_declarada'] ?? '',
                    $_POST['observaciones'] ?? '',
                    $_POST['presupuesto'] ?? '',
                    $_SESSION['user_id']
                );
                $this->redirect("view.php?id=$id_reparacion&success=created");
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $this->render_view(__DIR__ . '/../Views/add.php', [
            'error' => $error
        ]);
    }

    public function view()
    {
        $this->check_auth();
        $this->check_access('taller');

        $id_reparacion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id_reparacion) {
            $this->redirect("index.php");
        }

        $error = '';
        $success = '';

        if (isset($_GET['success']) && $_GET['success'] === 'created') {
            $success = 'Orden de reparación creada con éxito.';
        }

        // Action: update_status
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
            try {
                $this->workshop_model->update_repair_status_and_budget(
                    $id_reparacion,
                    $_POST['estado'] ?? '',
                    $_POST['presupuesto'] ?? '',
                    $_POST['nota_historial'] ?? '',
                    $_SESSION['user_id']
                );
                $success = 'Orden actualizada correctamente.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Action: add_repuesto
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_repuesto') {
            try {
                $id_producto = (int)($_POST['id_producto'] ?? 0);
                $this->workshop_model->add_repuesto_to_repair($id_reparacion, $id_producto, 1);
                $success = 'Repuesto asignado y descontado del inventario.';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $repair = $this->workshop_model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            $this->redirect("index.php");
        }

        $repuestos = $this->workshop_model->get_repair_repuestos($id_reparacion);
        $historial = $this->workshop_model->get_repair_historial($id_reparacion);
        $productos_opt = $this->workshop_model->get_active_products_in_stock();

        $this->render_view(__DIR__ . '/../Views/view.php', [
            'repair' => $repair,
            'repuestos' => $repuestos,
            'historial' => $historial,
            'productos_opt' => $productos_opt,
            'success' => $success,
            'error' => $error,
            'id_reparacion' => $id_reparacion
        ]);
    }

    public function print_receipt()
    {
        $this->check_auth();

        $id_reparacion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id_reparacion) {
            die("ID no válido.");
        }

        $repair = $this->workshop_model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            die("Orden no encontrada.");
        }

        $this->render_view(__DIR__ . '/../Views/print_receipt.php', [
            'repair' => $repair
        ]);
    }

    public function track()
    {
        // Publicly accessible tracking page
        $codigo = isset($_GET['code']) ? trim($_GET['code']) : '';
        $order_found = false;
        $repair = null;

        if ($codigo) {
            $repair = $this->workshop_model->find_repair_by_code($codigo);
            if ($repair) {
                $order_found = true;
            }
        }

        $this->render_view(__DIR__ . '/../Views/track.php', [
            'codigo' => $codigo,
            'order_found' => $order_found,
            'repair' => $repair
        ]);
    }
}
?>

<?php
// modules/workshop/Controllers/WorkshopController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/WorkshopModel.php';

class WorkshopController extends BaseController
{
    private $workshop_model;

    public function __construct()
    {
        // No requiere login en construct por ser track() una acción pública
        // Se llama a parent::__construct() para iniciar la sesión
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
                    $_POST['id_cliente_existente'] ?? 0,
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

                // Handle image uploads
                if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
                    $upload_dir = __DIR__ . '/../../../../assets/img/reparaciones/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    for ($i = 0; $i < count($_FILES['fotos']['tmp_name']); $i++) {
                        if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['fotos']['tmp_name'][$i];
                            $name = basename($_FILES['fotos']['name'][$i]);
                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            
                            // Simple validation
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array($ext, $allowed_types)) {
                                $new_name = 'rep_' . $id_reparacion . '_' . time() . '_' . $i . '.' . $ext;
                                $dest = $upload_dir . $new_name;
                                if (move_uploaded_file($tmp_name, $dest)) {
                                    $this->workshop_model->add_repair_image($id_reparacion, $new_name, 'Ingreso');
                                }
                            }
                        }
                    }
                }

                $this->redirect("index.php?action=view&id=$id_reparacion&success=created");
            } catch (Exception $e) {
                $this->redirect("index.php?action=add&error=" . urlencode($e->getMessage()));
            }
        }

        $clients = $this->workshop_model->get_all_clients();

        $this->render_view(__DIR__ . '/../Views/add.php', [
            'error' => $error,
            'clients' => $clients
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
                $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Orden actualizada correctamente."));
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        // Action: add_repuesto
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_repuesto') {
            try {
                $id_producto = (int)($_POST['id_producto'] ?? 0);
                $this->workshop_model->add_repuesto_to_repair($id_reparacion, $id_producto, 1);
                $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Repuesto asignado y descontado del inventario."));
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        // Action: add_image
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_image') {
            try {
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../../../assets/img/reparaciones/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $tmp_name = $_FILES['foto']['tmp_name'];
                    $name = basename($_FILES['foto']['name']);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $tipo = $_POST['tipo_imagen'] ?? 'Progreso';
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed_types)) {
                        $new_name = 'rep_' . $id_reparacion . '_' . time() . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $this->workshop_model->add_repair_image($id_reparacion, $new_name, $tipo);
                            $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Imagen subida correctamente."));
                        } else {
                            throw new Exception("Error al guardar la imagen en el servidor.");
                        }
                    } else {
                        throw new Exception("Formato de imagen no permitido.");
                    }
                } else {
                    throw new Exception("No se seleccionó ninguna imagen o hubo un error en la subida.");
                }
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        // Action: delete_image
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image') {
            try {
                $id_imagen = (int)($_POST['id_imagen'] ?? 0);
                $ruta_eliminada = $this->workshop_model->delete_repair_image($id_imagen, $id_reparacion);
                if ($ruta_eliminada) {
                    $file_path = __DIR__ . '/../../../../assets/img/reparaciones/' . $ruta_eliminada;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Imagen eliminada correctamente."));
                } else {
                    throw new Exception("Error al eliminar la imagen.");
                }
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        $repair = $this->workshop_model->find_repair_by_id($id_reparacion);
        if (!$repair) {
            $this->redirect("index.php");
        }

        $repuestos = $this->workshop_model->get_repair_repuestos($id_reparacion);
        $historial = $this->workshop_model->get_repair_historial($id_reparacion);
        $productos_opt = $this->workshop_model->get_active_products_in_stock();
        $images = $this->workshop_model->get_repair_images($id_reparacion);

        $this->render_view(__DIR__ . '/../Views/view.php', [
            'repair' => $repair,
            'repuestos' => $repuestos,
            'historial' => $historial,
            'productos_opt' => $productos_opt,
            'images' => $images,
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
        // Página de seguimiento de acceso público
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

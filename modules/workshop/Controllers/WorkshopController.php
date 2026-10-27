<?php
// modules/workshop/Controllers/WorkshopController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/WorkshopModel.php';

class WorkshopController extends BaseController
{
    private WorkshopModel $workshop_model;

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

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total_items = $this->workshop_model->get_repairs_count($estado_filter, $search);
        $total_pages = ceil($total_items / $limit);

        $repairs = $this->workshop_model->get_repairs($estado_filter, $search, $limit, $offset);

        $this->render_view(__DIR__ . '/../Views/index.php', [
            'repairs' => $repairs,
            'estado_filter' => $estado_filter,
            'search' => $search,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'limit' => $limit
        ]);
    }

    public function history()
    {
        $this->check_auth();
        $this->check_access('taller');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total_items = $this->workshop_model->get_paid_repairs_history_count();
        $total_pages = ceil($total_items / $limit);

        $paid_repairs = $this->workshop_model->get_paid_repairs_history($limit, $offset);

        $this->render_view(__DIR__ . '/../Views/history.php', [
            'paid_repairs' => $paid_repairs,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'limit' => $limit
        ]);
    }

    public function add()
    {
        $this->check_auth();
        $this->check_access('taller');
        
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/workshop/index.php");
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
                $this->redirect("index.php?action=add&error=" . urlencode("Token CSRF inválido"));
            }
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
                    isset($_POST['presupuesto']) && $_POST['presupuesto'] !== '' ? (float)$_POST['presupuesto'] : 0.0,
                    $_SESSION['user_id']
                );

                // Handle image uploads
                if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
                    $upload_dir = __DIR__ . '/../../../assets/img/reparaciones/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);

                    for ($i = 0; $i < count($_FILES['fotos']['tmp_name']); $i++) {
                        if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp_name = $_FILES['fotos']['tmp_name'][$i];
                            $name = basename($_FILES['fotos']['name'][$i]);
                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            $mime = $finfo->file($tmp_name);
                            
                            if (in_array($ext, $allowed_types, true) && in_array($mime, $allowed_mimes, true) && @getimagesize($tmp_name) !== false) {
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode("Token CSRF inválido"));
            }
        }

        // Action: send_to_cashier
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_to_cashier') {
            try {
                // Verificar que la orden exista y esté estrictamente en estado Listo
                $currentRepair = $this->workshop_model->find_repair_by_id($id_reparacion);
                if (!$currentRepair) {
                    throw new Exception("Orden de reparación no encontrada.");
                }
                if ($currentRepair['estado'] !== 'Listo') {
                    throw new Exception("Solo se pueden enviar a Ventas las órdenes que se encuentren en estado 'Listo'.");
                }

                $raw_presupuesto = str_replace(',', '.', (string)($_POST['presupuesto'] ?? ''));
                $presupuesto = (is_numeric($raw_presupuesto) && (float)$raw_presupuesto > 0) 
                    ? (float)$raw_presupuesto 
                    : (float)($currentRepair['presupuesto'] ?? 0);
                $nota = trim($_POST['nota_resolucion'] ?? '');
                $this->workshop_model->send_repair_to_cashier($id_reparacion, $presupuesto, $nota, (int)$_SESSION['user_id']);
                $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("¡Reparación enviada a Ventas para su cobro exitosamente!"));
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        // Action: update_status
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
            try {
                $estado = trim($_POST['estado'] ?? '');
                $raw_presupuesto = str_replace(',', '.', (string)($_POST['presupuesto'] ?? ''));
                $presupuesto = is_numeric($raw_presupuesto) ? (float)$raw_presupuesto : 0;
                
                $this->workshop_model->update_repair_status_and_budget(
                    $id_reparacion,
                    $estado,
                    $presupuesto,
                    trim($_POST['nota_historial'] ?? ''),
                    $_SESSION['user_id']
                );
                
                if ($estado === 'Listo') {
                    $msg = "¡Orden actualizada a 'Listo'! Ya puedes enviarla a Ventas para su cobro.";
                } else {
                    $msg = "Orden actualizada correctamente.";
                }

                $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode($msg));
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

        // Action: remove_repuesto
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_repuesto') {
            try {
                $id_rr = (int)($_POST['id_reparacion_repuesto'] ?? 0);
                $this->workshop_model->remove_repuesto_from_repair($id_rr);
                $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Repuesto devuelto al inventario."));
            } catch (Exception $e) {
                $this->redirect("index.php?action=view&id=$id_reparacion&error=" . urlencode($e->getMessage()));
            }
        }

        // Action: add_image
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_image') {
            try {
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../../assets/img/reparaciones/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $tmp_name = $_FILES['foto']['tmp_name'];
                    $name = basename($_FILES['foto']['name']);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $tipo = $_POST['tipo_imagen'] ?? 'Progreso';
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp_name);

                    if (in_array($ext, $allowed_types, true) && in_array($mime, $allowed_mimes, true) && @getimagesize($tmp_name) !== false) {
                        $new_name = 'rep_' . $id_reparacion . '_' . time() . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $this->workshop_model->add_repair_image($id_reparacion, $new_name, $tipo);
                            $this->redirect("index.php?action=view&id=$id_reparacion&success=" . urlencode("Imagen subida correctamente."));
                        } else {
                            throw new Exception("Error al guardar la imagen en el servidor.");
                        }
                    } else {
                        throw new Exception("El archivo seleccionado no es una imagen válida o su formato no está permitido.");
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
                    $file_path = __DIR__ . '/../../../assets/img/reparaciones/' . $ruta_eliminada;
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
        $this->check_access('taller');

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

    public function delivery_receipt()
    {
        // Se imprime desde el taller y también desde la caja (Cobrar Reparaciones)
        $this->check_any_access(['taller', 'cobrar_reparaciones', 'ventas', 'venta']);

        $id_reparacion = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id_reparacion) {
            die("ID no válido.");
        }

        $data = $this->workshop_model->get_delivery_receipt_data($id_reparacion);
        if (!$data || empty($data['repair'])) {
            die("Orden de reparación no encontrada.");
        }

        $this->render_view(__DIR__ . '/../Views/delivery_receipt.php', $data);
    }

    public function check_status()
    {
        $this->check_auth(true);
        header('Content-Type: application/json; charset=utf-8');

        if (isset($_SESSION['role']) && $_SESSION['role'] === 'guest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
            exit();
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID no válido']);
            exit();
        }

        $repair = $this->workshop_model->find_repair_by_id($id);
        if (!$repair) {
            echo json_encode(['success' => false, 'error' => 'Orden no encontrada']);
            exit();
        }

        echo json_encode([
            'success' => true,
            'id_reparacion' => $id,
            'estado' => $repair['estado'],
            'fecha_entrega' => $repair['fecha_entrega'],
            'is_delivered' => ($repair['estado'] === 'Entregado')
        ]);
        exit();
    }
}

<?php
// modules/inventory/Controllers/InventoryController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/InventoryModel.php';

class InventoryController extends BaseController
{
    private InventoryModel $inventory_model;

    public function __construct()
    {
        parent::__construct();
        $this->inventory_model = new InventoryModel();
    }

    public function inventory()
    {
        $this->check_access('inventario');

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
        $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

        $categorias = $this->inventory_model->get_all_categories();
        $all_products = $this->inventory_model->get_all_products_catalog();
        $stats = $this->inventory_model->get_inventory_stats();

        $this->render_view(__DIR__ . '/../Views/inventory.php', [
            'all_products' => $all_products,
            'stats' => $stats,
            'search' => $search,
            'filter' => $filter,
            'categoria' => $categoria,
            'categorias' => $categorias
        ]);
    }

    public function change_status()
    {
        $isAjax = $this->is_ajax();

        $this->check_access('inventario', $isAjax);
        if (($_SESSION['role'] ?? '') !== 'admin') {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Acceso restringido a administradores']);
                exit();
            }
            $this->redirect(BASE_URL . "/modules/inventory/index.php");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                http_response_code(405);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit();
            }
            $this->redirect(BASE_URL . "/modules/inventory/index.php");
        }

        if (!$this->verify_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                exit();
            }
            $this->redirect(BASE_URL . "/modules/inventory/index.php?error=" . urlencode("Token CSRF inválido"));
        }

        $id_producto = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id_producto > 0) {
            if ($this->inventory_model->toggle_product_status($id_producto)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
                    exit();
                }
                $this->redirect(BASE_URL . "/modules/inventory/index.php?msg=status_updated");
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
                    exit();
                }
                $this->redirect(BASE_URL . "/modules/inventory/index.php?error=update_failed");
            }
        } else {
            $this->redirect(BASE_URL . "/modules/inventory/index.php");
        }
    }

    public function check_stock()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->check_any_access(['inventario', 'venta', 'taller'], true);

        $id_producto = isset($_GET['id_producto']) ? (int) $_GET['id_producto'] : 0;

        if ($id_producto <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de producto inválido']);
            exit();
        }

        $row = $this->inventory_model->find_product_by_id($id_producto);

        if ($row) {
            echo json_encode([
                'id_producto' => $id_producto,
                'nombre' => $row['nombre'],
                'stock' => (int) $row['cantidad']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
        }
        exit;
    }

    public function ajax_save_product()
    {
        $this->check_access('inventario', true);
        header('Content-Type: application/json; charset=utf-8');
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Validación Anti-CSRF
        $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$this->verify_csrf($token)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o expirado.']);
            exit();
        }

        $id = isset($input['id']) ? intval($input['id']) : 0;
        $nombre = trim($input['nombre'] ?? '');
        $marca = trim($input['marca'] ?? '');
        $modelo = trim($input['modelo'] ?? '');
        $sku = trim($input['sku'] ?? ($input['codigo'] ?? ''));
        $categoria = trim($input['categoria'] ?? 'Celulares');
        $cantidad = isset($input['cantidad']) ? intval($input['cantidad']) : 0;
        $minimo = isset($input['minimo']) ? intval($input['minimo']) : (isset($input['stock_minimo']) ? intval($input['stock_minimo']) : 3);
        $precio = isset($input['precio']) ? floatval($input['precio']) : 0;
        $activo = isset($input['activo']) ? ($input['activo'] ? 1 : 0) : 1;

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre del producto es obligatorio']);
            exit();
        }
        if ($precio < 0 || $cantidad < 0 || $minimo < 0) {
            echo json_encode(['success' => false, 'message' => 'El precio, stock y stock mínimo no pueden ser negativos']);
            exit();
        }

        try {
            require_once __DIR__ . '/../../../config/audit.php';
            if ($id > 0) {
                // Actualizar producto existente
                $product_data = [
                    'nombre' => $nombre,
                    'precio' => $precio,
                    'is_active' => $activo
                ];
                $detail_data = [
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'categoria' => $categoria,
                    'codigo' => $sku,
                    'stock_minimo' => $minimo
                ];
                $this->inventory_model->update_product_transaction($id, $product_data, $detail_data, $cantidad);
                audit_log($this->conn, 'INVENTORY_UPDATE', $_SESSION['user_id'], 'Inventario', $id, "Actualizado producto ID $id: $nombre");
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Producto actualizado']);
            } else {
                // Crear nuevo producto
                $product_data = [
                    'nombre' => $nombre,
                    'precio' => $precio
                ];
                $detail_data = [
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'categoria' => $categoria,
                    'codigo' => $sku,
                    'descripcion' => '',
                    'stock_minimo' => $minimo
                ];
                $id_nuevo = $this->inventory_model->create_product_transaction($product_data, $detail_data, $cantidad);
                if (!$activo) {
                    $this->inventory_model->deactivate_product($id_nuevo);
                }
                audit_log($this->conn, 'INVENTORY_CREATE', $_SESSION['user_id'], 'Inventario', $id_nuevo, "Registrado nuevo producto en inventario ID $id_nuevo: $nombre");
                echo json_encode(['success' => true, 'id' => $id_nuevo, 'message' => 'Producto añadido al inventario']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

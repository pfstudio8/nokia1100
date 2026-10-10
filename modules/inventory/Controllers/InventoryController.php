<?php
// modules/inventory/Controllers/InventoryController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/InventoryModel.php';

class InventoryController extends BaseController
{
    private $inventory_model;

    public function __construct()
    {
        parent::__construct();
        $this->inventory_model = new InventoryModel();
    }

    public function inventory()
    {
        $this->check_access('inventario');

        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total_items = $this->inventory_model->get_inventory_count($search, $filter);
        $total_pages = ceil($total_items / $limit);

        $items = $this->inventory_model->get_inventory($search, $filter, $limit, $offset);

        $this->render_view(__DIR__ . '/../Views/inventory.php', [
            'items' => $items,
            'search' => $search,
            'filter' => $filter,
            'page' => $page,
            'total_pages' => $total_pages
        ]);
    }

    public function add_product()
    {
        $this->check_access('inventario');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $this->render_view(__DIR__ . '/../Views/add_product.php');
    }

    public function process_product()
    {
        $this->check_access('inventario');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nombre = $_POST['nombre'] ?? '';
            $marca = $_POST['marca'] ?? '';
            $modelo = $_POST['modelo'] ?? '';
            $precio = $_POST['precio'] ?? 0;
            $cantidad = $_POST['cantidad'] ?? 0;

            $categoria = $_POST['categoria'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $stock_minimo = $_POST['stock_minimo'] ?? 0;

            if (empty($nombre) || empty($marca) || empty($modelo) || empty($precio) || $cantidad === '' || empty($categoria)) {
                $this->redirect("index.php?action=add_product&error=" . urlencode("Los campos principales son obligatorios"));
            }

            $precio = floatval($precio);
            $cantidad = intval($cantidad);
            $stock_minimo = intval($stock_minimo);

            try {
                $product_data = [
                    'nombre' => $nombre,
                    'precio' => $precio
                ];
                $detail_data = [
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'categoria' => $categoria,
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'stock_minimo' => $stock_minimo
                ];

                $id_nuevo = $this->inventory_model->create_product_transaction($product_data, $detail_data, $cantidad);

                require_once __DIR__ . '/../../../config/audit.php';
                audit_log($this->conn, 'INVENTORY_CREATE', $_SESSION['user_id'], 'Inventario', $id_nuevo, "Registrado nuevo producto en inventario: $nombre (Stock inicial: $cantidad)");

                $this->redirect("index.php?success=created");
            } catch (Exception $e) {
                $this->redirect("index.php?action=add_product&error=" . urlencode($e->getMessage()));
            }
        } else {
            $this->redirect("inventory.php");
        }
    }

    public function add_stock()
    {
        $this->check_auth();

        $message = '';
        $messageType = '';
        $nombre = $marca = $modelo = $precio = $cantidad = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nombre = $_POST['nombre'] ?? '';
            $marca = $_POST['marca'] ?? '';
            $modelo = $_POST['modelo'] ?? '';
            $precio = $_POST['precio'] ?? '';
            $cantidad = $_POST['cantidad'] ?? '';

            if (empty($nombre) || empty($marca) || empty($modelo) || empty($precio) || empty($cantidad)) {
                $this->redirect("index.php?action=add_stock&error=" . urlencode("Todos los campos son obligatorios"));
            } else {
                try {
                    $product_data = [
                        'nombre' => $nombre,
                        'precio' => floatval($precio)
                    ];
                    $detail_data = [
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'categoria' => 'Accesorios',
                        'codigo' => '',
                        'descripcion' => '',
                        'stock_minimo' => 2
                    ];

                    $id_nuevo = $this->inventory_model->create_product_transaction($product_data, $detail_data, intval($cantidad));

                    require_once __DIR__ . '/../../../config/audit.php';
                    audit_log($this->conn, 'INVENTORY_CREATE', $_SESSION['user_id'], 'Inventario', $id_nuevo, "Agregado nuevo producto: $nombre (Stock inicial: $cantidad)");

                    $this->redirect("index.php?action=add_stock&success=" . urlencode("Producto agregado exitosamente"));
                } catch (Exception $e) {
                    $this->redirect("index.php?action=add_stock&error=" . urlencode("Error: " . $e->getMessage()));
                }
            }
        }

        $this->render_view(__DIR__ . '/../Views/add_stock.php', [
            'message' => $message,
            'messageType' => $messageType,
            'nombre' => $nombre,
            'marca' => $marca,
            'modelo' => $modelo,
            'precio' => $precio,
            'cantidad' => $cantidad
        ]);
    }

    public function edit_stock()
    {
        $this->check_access('inventario');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id_producto === 0) {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $message = '';
        $messageType = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nombre = $_POST['nombre'] ?? '';
            $marca = $_POST['marca'] ?? '';
            $modelo = $_POST['modelo'] ?? '';
            $precio = $_POST['precio'] ?? '';
            $cantidad = $_POST['cantidad'] ?? '';

            if (empty($nombre) || empty($marca) || empty($modelo) || empty($precio) || empty($cantidad)) {
                $this->redirect("index.php?action=edit_stock&id=$id_producto&error=" . urlencode("Todos los campos son obligatorios"));
            } else {
                try {
                    $product_data = ['nombre' => $nombre, 'precio' => floatval($precio)];
                    $detail_data = ['marca' => $marca, 'modelo' => $modelo];
                    $this->inventory_model->update_product_transaction($id_producto, $product_data, $detail_data, intval($cantidad));

                    require_once __DIR__ . '/../../../config/audit.php';
                    audit_log($this->conn, 'INVENTORY_UPDATE', $_SESSION['user_id'], 'Inventario', $id_producto, "Actualizado stock/detalles de producto ID $id_producto: $nombre");

                    $this->redirect("index.php?action=edit_stock&id=$id_producto&success=" . urlencode("Producto actualizado exitosamente"));
                } catch (Exception $e) {
                    $this->redirect("index.php?action=edit_stock&id=$id_producto&error=" . urlencode("Error: " . $e->getMessage()));
                }
            }
        }

        $product = $this->inventory_model->find_product_by_id($id_producto);
        if (!$product) {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $this->render_view(__DIR__ . '/../Views/edit_stock.php', [
            'id_producto' => $id_producto,
            'nombre' => $product['nombre'],
            'marca' => $product['marca'],
            'modelo' => $product['modelo'],
            'precio' => $product['precio'],
            'cantidad' => $product['cantidad'],
            'message' => $message,
            'messageType' => $messageType
        ]);
    }

    public function change_status()
    {
        $this->check_access('inventario');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['session_token']) {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php?error=" . urlencode("Token CSRF inválido"));
        }

        $id_producto = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id_producto > 0) {
            if ($this->inventory_model->toggle_product_status($id_producto)) {
                $this->redirect(BASE_URL . "/modules/inventory/inventory.php?msg=status_updated");
            } else {
                $this->redirect(BASE_URL . "/modules/inventory/inventory.php?error=update_failed");
            }
        } else {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }
    }

    public function delete_product()
    {
        $this->check_access('inventario');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id_producto === 0) {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php");
        }

        $total_sales_purchases = $this->inventory_model->check_product_sales_purchases($id_producto);

        try {
            if ($total_sales_purchases > 0) {
                $this->inventory_model->deactivate_product_and_delete_stock($id_producto);
                $tipo_borrado = 'desactivado lógicamente';
            } else {
                $this->inventory_model->delete_product_completely($id_producto);
                $tipo_borrado = 'eliminado físicamente';
            }

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'INVENTORY_DELETE', $_SESSION['user_id'], 'Inventario', $id_producto, "Producto ID $id_producto $tipo_borrado");
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php?success=deleted");
        } catch (Exception $e) {
            $this->redirect(BASE_URL . "/modules/inventory/inventory.php?error=" . urlencode($e->getMessage()));
        }
    }

    public function check_stock()
    {
        header('Content-Type: application/json');
        $this->check_auth();

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

    public function ajax_create_product()
    {
        $this->check_access('inventario');
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['nombre']) || !isset($input['precio'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit();
        }

        $nombre = $input['nombre'];
        $marca = $input['marca'] ?? '';
        $modelo = $input['modelo'] ?? '';
        $categoria = $input['categoria'] ?? 'Celular';
        $precio = floatval($input['precio']);

        try {
            $product_data = [
                'nombre' => $nombre,
                'precio' => $precio
            ];
            $detail_data = [
                'marca' => $marca,
                'modelo' => $modelo,
                'categoria' => $categoria,
                'codigo' => '',
                'descripcion' => '',
                'stock_minimo' => 0
            ];

            $id_producto = $this->inventory_model->create_product_transaction($product_data, $detail_data, 0);
            echo json_encode(['success' => true, 'id_producto' => $id_producto, 'message' => 'Producto creado']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function get_purchase_history()
    {
        header('Content-Type: application/json');
        $this->check_auth();

        $id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id_producto <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
            exit();
        }

        try {
            $purchases = $this->inventory_model->get_purchase_history($id_producto);
            echo json_encode(['success' => true, 'purchases' => $purchases]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function update_images()
    {
        $this->check_access('inventario');
        try {
            $this->inventory_model->update_images_db();
            echo "Imágenes actualizadas correctamente.";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        exit;
    }
}
?>
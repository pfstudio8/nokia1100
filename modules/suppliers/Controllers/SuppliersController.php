<?php
// modules/suppliers/Controllers/SuppliersController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../../../config/audit.php';
require_once __DIR__ . '/../Models/SupplierModel.php';
require_once __DIR__ . '/../../inventory/Models/InventoryModel.php';

class SuppliersController extends BaseController
{
    private SupplierModel $supplier_model;

    public function __construct()
    {
        parent::__construct();
        $this->supplier_model = new SupplierModel();
    }

    /**
     * Renderiza el hub unificado de Abastecimiento y Proveedores (Nokia 1100 UI Kit)
     */
    private function render_hub(string $initial_view = 'sec1', int $preselected_supplier = 0): void
    {
        $suppliers = $this->supplier_model->get_suppliers();
        $supplier_list = [];
        foreach ($suppliers as $s) {
            $id_prov = (int)$s['id_proveedor'];
            $s['total_compras'] = $this->supplier_model->count_supplier_purchases($id_prov);
            $s['history'] = $this->supplier_model->get_purchase_details_by_supplier($id_prov);
            $supplier_list[] = $s;
        }

        $inventory_model = new InventoryModel();
        $inventory = $inventory_model->get_all_products_catalog();

        $purchases = $this->supplier_model->get_purchases();
        $purchase_list = [];
        foreach ($purchases as $p) {
            $p['details'] = $this->supplier_model->get_purchase_details($p['id_compra']);
            $purchase_list[] = $p;
        }

        if (!empty($_GET['view']) && in_array($_GET['view'], ['sec1', 'sec2', 'sec3', 'sec4', 'secStock'], true)) {
            $initial_view = $_GET['view'];
        }

        $this->render_view(__DIR__ . '/../Views/list.php', [
            'suppliers' => $supplier_list,
            'inventory' => $inventory,
            'purchases' => $purchase_list,
            'initial_view' => $initial_view,
            'preselected_supplier' => $preselected_supplier
        ]);
    }

    public function suppliers()
    {
        $this->check_access('proveedores');

        // Gestionar añadir proveedor (Formulario tradicional POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
            if (!$this->verify_csrf($_POST['csrf_token'] ?? null)) {
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?error=" . urlencode("Token CSRF inválido."));
            }
            $nombre = trim($_POST['nombre'] ?? '');
            $domicilio = trim($_POST['domicilio'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $atencion = trim($_POST['atencion'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($nombre !== '' && $this->supplier_model->create_supplier($nombre, $domicilio, $telefono, $atencion, $email)) {
                $new_id = $this->conn->insert_id;
                audit_log($this->conn, 'SUPPLIER_CREATE', $_SESSION['user_id'] ?? null, 'proveedor', $new_id, "Creado proveedor: $nombre");
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?success=created");
            } else {
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?error=" . urlencode("Error al crear el proveedor."));
            }
        }

        // Gestiona la eliminación del proveedor (POST tradicional o JSON AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'delete_supplier' || ($_GET['action'] ?? '') === 'delete_supplier')) {
            $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
            $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;
            $csrf = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

            if (!$this->verify_csrf($csrf)) {
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
                    exit;
                }
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?error=" . urlencode("Token CSRF inválido."));
            }

            $id = intval($input['id_proveedor'] ?? 0);
            $count = $this->supplier_model->count_supplier_purchases($id);
            if ($count > 0) {
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => "No se puede eliminar: este proveedor tiene $count orden(es) asociada(s)."]);
                    exit;
                }
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?error=has_purchases");
            } else {
                $this->supplier_model->delete_supplier($id);
                audit_log($this->conn, 'SUPPLIER_DELETE', $_SESSION['user_id'] ?? null, 'proveedor', $id, "Eliminado proveedor ID: $id");
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => 'Proveedor eliminado del directorio.']);
                    exit;
                }
                $this->redirect(BASE_URL . "/modules/suppliers/index.php?success=deleted");
            }
        }

        $preselected = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : 0;
        $this->render_hub('sec1', $preselected);
    }

    /**
     * Alta o edición rápida de proveedor vía AJAX JSON (desde #supplierModal)
     */
    public function save_supplier()
    {
        $this->check_access('proveedores', true);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $csrf_token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$this->verify_csrf($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        $id_proveedor = isset($input['id_proveedor']) ? (int)$input['id_proveedor'] : 0;
        $nombre = trim((string)($input['nombre'] ?? ''));
        $atencion = trim((string)($input['atencion'] ?? ''));
        $telefono = trim((string)($input['telefono'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $domicilio = trim((string)($input['domicilio'] ?? ''));

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'La razón social o nombre del proveedor es obligatoria.']);
            exit;
        }

        try {
            if ($id_proveedor > 0) {
                if (!$this->supplier_model->update_supplier($id_proveedor, $nombre, $domicilio, $telefono, $atencion, $email)) {
                    throw new Exception('No se pudo actualizar el proveedor.');
                }
                audit_log($this->conn, 'SUPPLIER_UPDATE', $_SESSION['user_id'] ?? null, 'proveedor', $id_proveedor, "Actualizados datos de proveedor: $nombre");
                $total_compras = $this->supplier_model->count_supplier_purchases($id_proveedor);
                echo json_encode([
                    'success' => true,
                    'mode' => 'update',
                    'message' => "Proveedor «{$nombre}» actualizado.",
                    'supplier' => [
                        'id' => $id_proveedor,
                        'nombre' => $nombre,
                        'atencion' => $atencion,
                        'telefono' => $telefono,
                        'email' => $email,
                        'domicilio' => $domicilio,
                        'total_compras' => $total_compras
                    ]
                ]);
                exit;
            } else {
                if (!$this->supplier_model->create_supplier($nombre, $domicilio, $telefono, $atencion, $email)) {
                    throw new Exception('No se pudo registrar el proveedor.');
                }
                $new_id = (int)$this->conn->insert_id;
                audit_log($this->conn, 'SUPPLIER_CREATE', $_SESSION['user_id'] ?? null, 'proveedor', $new_id, "Creado proveedor: $nombre");
                echo json_encode([
                    'success' => true,
                    'mode' => 'create',
                    'message' => "Proveedor «{$nombre}» incorporado al directorio.",
                    'supplier' => [
                        'id' => $new_id,
                        'nombre' => $nombre,
                        'atencion' => $atencion,
                        'telefono' => $telefono,
                        'email' => $email,
                        'domicilio' => $domicilio,
                        'total_compras' => 0
                    ]
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Creación rápida de producto para Orden Manual (#quickModal)
     */
    public function quick_product()
    {
        $this->check_access('proveedores', true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $csrf_token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$this->verify_csrf($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        $sku = trim((string)($input['sku'] ?? ''));
        $nombre = trim((string)($input['nombre'] ?? ''));
        $marca = trim((string)($input['marca'] ?? ''));
        $modelo = trim((string)($input['modelo'] ?? ''));
        $costo = max(0, (float)($input['costo'] ?? 0));
        $minimo = max(1, (int)($input['minimo'] ?? 3));
        $categoria = trim((string)($input['categoria'] ?? 'Accesorios')) ?: 'Accesorios';

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre del producto es obligatorio.']);
            exit;
        }

        try {
            $prod = $this->supplier_model->quick_create_product($sku, $nombre, $marca, $modelo, $costo, $minimo, $categoria);
            audit_log($this->conn, 'PRODUCT_QUICK_CREATE', $_SESSION['user_id'] ?? null, 'producto', $prod['id_producto'], "Creado producto rápido desde Abastecimiento: $nombre ({$prod['sku']})");
            echo json_encode([
                'success' => true,
                'message' => "Producto «{$nombre}» creado y sumado a la orden.",
                'product' => $prod
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function edit_supplier()
    {
        $this->check_access('proveedores');

        $id_proveedor = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id_proveedor === 0) {
            $this->redirect("index.php");
        }

        $error = '';
        $success = '';

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->verify_csrf($_POST['csrf_token'] ?? null)) {
                $this->redirect("index.php?action=edit_supplier&id=$id_proveedor&error=" . urlencode("Token CSRF inválido."));
            }
            $nombre = trim($_POST['nombre'] ?? '');
            $domicilio = trim($_POST['domicilio'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $atencion = trim($_POST['atencion'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                $this->redirect("index.php?action=edit_supplier&id=$id_proveedor&error=" . urlencode("El nombre de la empresa es obligatorio."));
            } else {
                if ($this->supplier_model->update_supplier($id_proveedor, $nombre, $domicilio, $telefono, $atencion, $email)) {
                    audit_log($this->conn, 'SUPPLIER_UPDATE', $_SESSION['user_id'], 'proveedor', $id_proveedor, "Actualizados datos de proveedor: $nombre");
                    $this->redirect("index.php?success=" . urlencode("Proveedor actualizado correctamente."));
                } else {
                    $this->redirect("index.php?action=edit_supplier&id=$id_proveedor&error=" . urlencode("Error al actualizar el proveedor."));
                }
            }
        }

        $supplier = $this->supplier_model->find_supplier_by_id($id_proveedor);
        if (!$supplier) {
            $this->redirect("index.php");
        }

        $this->render_view(__DIR__ . '/../Views/edit.php', [
            'id_proveedor' => $id_proveedor,
            'nombre' => $supplier['nombre'],
            'domicilio' => $supplier['domicilio'],
            'telefono' => $supplier['telefono'],
            'atencion' => $supplier['atencion'],
            'email' => $supplier['email'],
            'error' => $error,
            'success' => $success
        ]);
    }

    public function new_purchase()
    {
        $this->check_access('proveedores');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            header('Content-Type: application/json; charset=utf-8');
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $csrf_token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
                if (!$this->verify_csrf($csrf_token)) {
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                    exit;
                }
                $id_proveedor = (int)($input['id_proveedor'] ?? 0);
                $items = $input['items'] ?? [];
                $descripcion = trim((string)($input['descripcion'] ?? ''));
                $tiempo_entrega = trim((string)($input['tiempo_entrega'] ?? ''));
                $iva = (float)($input['iva'] ?? 0);
                $autorizado_por = trim((string)($input['autorizado_por'] ?? ($_SESSION['username'] ?? 'Admin')));

                if ($id_proveedor <= 0 || empty($items)) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos: elegí un proveedor y al menos un artículo.']);
                    exit;
                }

                try {
                    $id_compra = $this->supplier_model->create_purchase_transaction($id_proveedor, $items, $descripcion, $tiempo_entrega, $iva, $autorizado_por);
                    audit_log($this->conn, 'PURCHASE_CREATE', $_SESSION['user_id'] ?? null, 'compra', $id_compra, "Registrada orden de compra #$id_compra para proveedor #$id_proveedor");
                    $details = $this->supplier_model->get_purchase_details($id_compra);
                    $sup = $this->supplier_model->find_supplier_by_id($id_proveedor);
                    echo json_encode([
                        'success' => true,
                        'id_compra' => $id_compra,
                        'message' => 'Orden de compra #' . str_pad((string)$id_compra, 4, '0', STR_PAD_LEFT) . ' registrada como Pendiente.',
                        'purchase' => [
                            'id_compra' => $id_compra,
                            'id_proveedor' => $id_proveedor,
                            'proveedor_nombre' => $sup['nombre'] ?? 'Proveedor',
                            'fecha' => date('Y-m-d H:i:s'),
                            'descripcion' => $descripcion,
                            'tiempo_entrega' => $tiempo_entrega,
                            'autorizado_por' => $autorizado_por,
                            'estado' => 'Pendiente',
                            'details' => $details
                        ]
                    ]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            }
        }

        $preselected = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        $view = ($_GET['mode'] ?? '') === 'manual' ? 'sec3' : 'sec2';
        $this->render_hub($view, $preselected);
    }

    public function purchase_history()
    {
        $this->check_access('proveedores');
        $preselected = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : 0;
        $this->render_hub('sec4', $preselected);
    }

    public function receive_purchase()
    {
        $this->check_access('proveedores', true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrf_token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!$this->verify_csrf($csrf_token)) {
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                exit;
            }
            $id_compra = (int)($input['id_compra'] ?? 0);
            $adjusted_items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

            if (!$id_compra) {
                echo json_encode(['success' => false, 'message' => 'ID de compra inválido']);
                exit;
            }

            try {
                $this->supplier_model->receive_purchase($id_compra, $adjusted_items);
                audit_log($this->conn, 'PURCHASE_RECEIVE', $_SESSION['user_id'] ?? null, 'compra', $id_compra, "Recepción verificada de pedido #$id_compra y stock acreditado");
                echo json_encode(['success' => true, 'message' => 'Recepción verificada: unidades acreditadas al inventario físico.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function cancel_purchase()
    {
        $this->check_access('proveedores', true);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrf_token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!$this->verify_csrf($csrf_token)) {
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                exit;
            }
            $id_compra = (int)($input['id_compra'] ?? 0);

            if (!$id_compra) {
                echo json_encode(['success' => false, 'message' => 'ID de compra inválido']);
                exit;
            }

            try {
                if ($this->supplier_model->cancel_purchase($id_compra)) {
                    audit_log($this->conn, 'PURCHASE_CANCEL', $_SESSION['user_id'] ?? null, 'compra', $id_compra, "Cancelación de orden de compra #$id_compra");
                    echo json_encode(['success' => true, 'message' => 'Orden de compra cancelada.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se pudo cancelar el pedido. Verifique su estado.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}

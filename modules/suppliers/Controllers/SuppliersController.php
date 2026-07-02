<?php
// modules/suppliers/Controllers/SuppliersController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/SupplierModel.php';

class SuppliersController extends BaseController
{
    private $supplier_model;

    public function __construct()
    {
        parent::__construct();
        $this->supplier_model = new SupplierModel();
    }

    public function suppliers()
    {
        $this->check_access('proveedores');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        // Handle Add Supplier
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
            $nombre = $_POST['nombre'] ?? '';
            $domicilio = $_POST['domicilio'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $atencion = $_POST['atencion'] ?? '';
            $email = $_POST['email'] ?? '';

            if ($this->supplier_model->create_supplier($nombre, $domicilio, $telefono, $atencion, $email)) {
                $this->redirect(BASE_URL . "/modules/suppliers/suppliers.php?msg=added");
            } else {
                $this->redirect(BASE_URL . "/modules/suppliers/suppliers.php?error=failed");
            }
        }

        // Gestiona la eliminación del proveedor
        if (isset($_GET['delete'])) {
            $id = intval($_GET['delete']);
            $count = $this->supplier_model->count_supplier_purchases($id);
            if ($count > 0) {
                $this->redirect(BASE_URL . "/modules/suppliers/suppliers.php?error=has_purchases");
            } else {
                $this->supplier_model->delete_supplier($id);
                $this->redirect(BASE_URL . "/modules/suppliers/suppliers.php?msg=deleted");
            }
        }

        $suppliers = $this->supplier_model->get_suppliers();

        // Prepara las compras de cada proveedor para la vista
        $supplier_list = [];
        foreach ($suppliers as $s) {
            $id_prov = $s['id_proveedor'];
            $total_compras = $this->supplier_model->count_supplier_purchases($id_prov);
            $history = $this->supplier_model->get_purchase_details_by_supplier($id_prov);
            $s['total_compras'] = $total_compras;
            $s['history'] = $history;
            $supplier_list[] = $s;
        }

        $this->render_view(__DIR__ . '/../Views/list.php', [
            'suppliers' => $supplier_list
        ]);
    }

    public function edit_supplier()
    {
        $this->check_access('proveedores');
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        $id_proveedor = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id_proveedor === 0) {
            $this->redirect("suppliers.php");
        }

        $error = '';
        $success = '';

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $nombre = trim($_POST['nombre'] ?? '');
            $domicilio = trim($_POST['domicilio'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $atencion = trim($_POST['atencion'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                $error = "El nombre de la empresa es obligatorio.";
            } else {
                if ($this->supplier_model->update_supplier($id_proveedor, $nombre, $domicilio, $telefono, $atencion, $email)) {
                    require_once __DIR__ . '/../../../config/audit.php';
                    audit_log($this->conn, 'SUPPLIER_UPDATE', $_SESSION['user_id'], 'proveedor', $id_proveedor, "Actualizados datos de proveedor: $nombre");
                    $success = "Proveedor actualizado correctamente.";
                } else {
                    $error = "Error al actualizar el proveedor.";
                }
            }
        }

        $supplier = $this->supplier_model->find_supplier_by_id($id_proveedor);
        if (!$supplier) {
            $this->redirect("suppliers.php");
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
        $this->check_auth();
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $id_proveedor = $input['id_proveedor'] ?? 0;
                $items = $input['items'] ?? [];
                $descripcion = $input['descripcion'] ?? '';
                $tiempo_entrega = $input['tiempo_entrega'] ?? '';
                $iva = $input['iva'] ?? 0;
                $autorizado_por = $input['autorizado_por'] ?? '';

                if (empty($id_proveedor) || empty($items)) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                    exit;
                }

                try {
                    $this->supplier_model->create_purchase_transaction($id_proveedor, $items, $descripcion, $tiempo_entrega, $iva, $autorizado_por);
                    echo json_encode(['success' => true, 'message' => 'Compra registrada con éxito']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            }
        }

        $suppliers = $this->supplier_model->get_suppliers();
        
        $this->render_view(__DIR__ . '/../Views/new_purchase.php', [
            'suppliers' => $suppliers
        ]);
    }

    public function purchase_history()
    {
        $this->check_auth();
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect(BASE_URL . "/index.php");
        }

        $purchases = $this->supplier_model->get_purchases();

        // Obtiene los detalles de la compra para la vista
        $purchase_list = [];
        foreach ($purchases as $p) {
            $p['details'] = $this->supplier_model->get_purchase_details($p['id_compra']);
            $purchase_list[] = $p;
        }

        $this->render_view(__DIR__ . '/../Views/purchase_history.php', [
            'purchases' => $purchase_list
        ]);
    }
}
?>

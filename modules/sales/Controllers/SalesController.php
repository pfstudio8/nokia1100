<?php
// modules/sales/Controllers/SalesController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../../../classes/Layout.php';
require_once __DIR__ . '/../Models/SalesModel.php';

class SalesController extends BaseController
{
    private SalesModel $sales_model;

    public function __construct()
    {
        parent::__construct();
        $this->sales_model = new SalesModel();
    }

    public function sales()
    {
        $this->check_access('venta');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $total_items = $this->sales_model->get_sales_count();
        $total_pages = ceil($total_items / $limit);

        $sales = $this->sales_model->get_sales($limit, $offset);

        $this->render_view(__DIR__ . '/../Views/list.php', [
            'sales' => $sales,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'limit' => $limit,
            'can_annul' => Layout::can('venta_anular')
        ]);
    }

    public function new_sale()
    {
        $this->check_access('venta');

        $maxPct = (($_SESSION['role'] ?? '') === 'admin')
            ? SalesModel::DESCUENTO_MAX_PCT_ADMIN
            : (Layout::can('venta_descuento') ? SalesModel::DESCUENTO_MAX_PCT_EMPLEADO : 0.0);

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);

            if ($input) {
                // Validación Anti-CSRF
                $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
                if (!$this->verify_csrf($token)) {
                    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o expirado.']);
                    exit;
                }

                $items = $input['items'] ?? [];
                $metodo_pago = isset($input['metodo_pago']) && is_string($input['metodo_pago']) ? trim($input['metodo_pago']) : '';
                $pagos = isset($input['pagos']) && is_array($input['pagos']) ? $input['pagos'] : [];
                $descripcion = $input['descripcion'] ?? '';
                $descuento = isset($input['descuento']) && is_array($input['descuento']) ? $input['descuento'] : [];

                if (empty($items) || (empty($pagos) && $metodo_pago === '')) {
                    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                    exit;
                }

                if (empty($pagos) && !in_array($metodo_pago, SalesModel::METODOS_PAGO, true)) {
                    echo json_encode(['success' => false, 'message' => 'Método de pago no válido']);
                    exit;
                }
                $descripcion = mb_substr((string) $descripcion, 0, 255);

                try {
                    $pagoPayload = !empty($pagos) ? $pagos : $metodo_pago;
                    $id_venta = $this->sales_model->create_sale_transaction($items, $pagoPayload, $_SESSION['user_id'], $descripcion, $descuento, $maxPct);
                    echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito', 'id_venta' => $id_venta]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            }
        }

        $products = $this->sales_model->get_active_products_in_stock();
        $caja_model = new CajaModel();
        $has_open_caja = ($caja_model->get_open_caja() !== null);

        $this->render_view(__DIR__ . '/../Views/new_sale.php', [
            'products'          => $products,
            'has_open_caja'     => $has_open_caja,
            'discount_max_pct'  => $maxPct
        ]);
    }

    public function invoice()
    {
        $this->check_access('venta');

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

    public function repairs_to_charge()
    {
        // 'cobrar_reparaciones' en Layout::checkAccess ya autoriza también a quienes tengan 'venta'
        $this->check_access('cobrar_reparaciones');

        require_once __DIR__ . '/../../workshop/Models/WorkshopModel.php';
        $workshop_model = new WorkshopModel();

        // Procesar cobro si viene por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'charge_repair') {
            try {
                // Validación Anti-CSRF
                if (!$this->verify_csrf($_POST['csrf_token'] ?? null)) {
                    throw new Exception("Token de seguridad inválido o expirado.");
                }

                $id_reparacion = (int)($_POST['id_reparacion'] ?? 0);
                $modo_pago = trim((string)($_POST['modo_pago'] ?? 'unico'));

                if ($modo_pago === 'combinado') {
                    $metodo1 = trim((string)($_POST['metodo_pago_1'] ?? ''));
                    $metodo2 = trim((string)($_POST['metodo_pago_2'] ?? ''));
                    $monto1 = (float)str_replace(',', '.', (string)($_POST['monto_pago_1'] ?? 0));
                    $monto2 = (float)str_replace(',', '.', (string)($_POST['monto_pago_2'] ?? 0));
                    $pagoPayload = [
                        ['metodo' => $metodo1, 'monto' => $monto1],
                        ['metodo' => $metodo2, 'monto' => $monto2]
                    ];
                } else {
                    $metodo = trim((string)($_POST['metodo_pago'] ?? 'Efectivo'));
                    if (!in_array($metodo, SalesModel::METODOS_PAGO, true)) {
                        throw new Exception("Método de pago no válido.");
                    }
                    $pagoPayload = $metodo;
                }

                // Ejecuta verificación de estado, bloqueo por concurrencia y registro de cobro en una transacción atómica
                $id_venta = $workshop_model->charge_and_deliver_repair($id_reparacion, $pagoPayload, (int)$_SESSION['user_id']);

                try {
                    require_once __DIR__ . '/../../workshop/Services/RepairNotifier.php';
                    RepairNotifier::notifyDelivered($id_reparacion, $id_venta === 0);
                } catch (Throwable $mailErr) {
                    error_log('notifyDelivered error: ' . $mailErr->getMessage());
                }

                $msgOk = ($id_venta === 0) ? "Equipo entregado sin cargo por garantía." : "Reparación cobrada y entregada correctamente.";
                $this->redirect("index.php?action=repairs_to_charge&success=" . urlencode($msgOk) . "&charged_id=" . $id_reparacion . "&id_venta=" . $id_venta);
            } catch (Exception $e) {
                $this->redirect("index.php?action=repairs_to_charge&error=" . urlencode($e->getMessage()));
            }
        }

        // Obtener todas las reparaciones pendientes de cobro/entrega
        $repairs = $workshop_model->get_chargeable_repairs();
        
        // Obtener los repuestos asignados a cada reparación
        $repairs_parts = [];
        foreach ($repairs as $r) {
            $repairs_parts[$r['id_reparacion']] = $workshop_model->get_repair_repuestos($r['id_reparacion']);
        }

        $caja_model = new CajaModel();
        $has_open_caja = ($caja_model->get_open_caja() !== null);

        $this->render_view(__DIR__ . '/../Views/repairs_to_charge.php', [
            'repairs'       => $repairs,
            'repairs_parts' => $repairs_parts,
            'has_open_caja' => $has_open_caja
        ]);
    }

    public function credit_note()
    {
        $this->check_access('venta');

        $id_venta = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id_venta === 0) {
            die("ID de venta no especificado.");
        }

        $venta = $this->sales_model->find_sale_by_id($id_venta);
        if (!$venta) {
            die("Venta no encontrada.");
        }

        if ($venta['estado'] !== 'anulada') {
            die("Esta venta no ha sido anulada, por lo que no se puede emitir una nota de crédito.");
        }

        $detalles = $this->sales_model->get_sale_details($id_venta);

        $this->render_view(__DIR__ . '/../Views/credit_note.php', [
            'venta' => $venta,
            'detalles' => $detalles
        ]);
    }

    public function rollback()
    {
        header('Content-Type: application/json');
        $this->check_access('venta', true);
        if (!Layout::can('venta_anular')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tenés permiso para anular ventas.']);
            exit;
        }
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($input && isset($input['id_venta'])) {
                // Validación Anti-CSRF
                $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
                if (!$this->verify_csrf($token)) {
                    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o expirado.']);
                    exit;
                }

                $id_venta = intval($input['id_venta']);
                
                try {
                    $this->sales_model->rollback_sale($id_venta, $_SESSION['user_id']);
                    echo json_encode(['success' => true, 'message' => 'Venta anulada y stock devuelto exitosamente']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Petición inválida']);
        exit;
    }

    public function check_ready_repairs()
    {
        $this->check_auth(true);
        header('Content-Type: application/json; charset=utf-8');

        // Solo quien tenga acceso a taller o cobro de reparaciones/ventas ve montos y órdenes (excluye automáticamente a guest)
        require_once __DIR__ . '/../../../classes/Layout.php';
        if (!Layout::hasAccess('taller') && !Layout::hasAccess('cobrar_reparaciones')) {
            echo json_encode(['success' => false, 'count' => 0, 'repairs' => []]);
            exit();
        }

        require_once __DIR__ . '/../../workshop/Models/WorkshopModel.php';
        $workshop_model = new WorkshopModel();

        $repairs = $workshop_model->get_chargeable_repairs();
        $count = count($repairs);

        $summary = [];
        foreach ($repairs as $r) {
            $presupuesto = floatval($r['presupuesto']);
            $repuestos = floatval($r['costo_total'] ?? 0);
            $total = $presupuesto + $repuestos;
            $summary[] = [
                'id' => (int)$r['id_reparacion'],
                'codigo' => $r['codigo_orden'],
                'cliente' => $r['cliente_nombre'],
                'equipo' => $r['equipo_marca'] . ' ' . $r['equipo_modelo'],
                'total' => $total,
                'total_formatted' => '$' . number_format($total, 2)
            ];
        }

        $received = $workshop_model->get_received_repairs();
        $received_count = count($received);
        $received_summary = [];
        foreach ($received as $rec) {
            $received_summary[] = [
                'id' => (int)$rec['id_reparacion'],
                'codigo' => $rec['codigo_orden'],
                'cliente' => $rec['cliente_nombre'],
                'equipo' => trim($rec['equipo_marca'] . ' ' . $rec['equipo_modelo']),
                'falla' => $rec['falla_declarada'] ?? '',
                'operario' => $rec['operario_nombre'] ?? 'Empleado',
                'fecha' => !empty($rec['fecha_ingreso']) ? date('d/m H:i', strtotime($rec['fecha_ingreso'])) . ' hs' : ''
            ];
        }

        echo json_encode([
            'success' => true,
            'count' => $count,
            'repairs' => $summary,
            'latest_id' => !empty($summary) ? $summary[count($summary) - 1]['id'] : 0,
            'received_count' => $received_count,
            'received_repairs' => $received_summary
        ]);
        exit();
    }
}


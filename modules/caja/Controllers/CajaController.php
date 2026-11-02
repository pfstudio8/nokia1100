<?php
// modules/caja/Controllers/CajaController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/CajaModel.php';

class CajaController extends BaseController
{
    private CajaModel $caja_model;

    public function __construct()
    {
        parent::__construct();
        $this->caja_model = new CajaModel();
    }

    /**
     * Vista principal del módulo Caja.
     */
    public function index()
    {
        $this->check_access('caja');

        $open_caja = $this->caja_model->get_open_caja();
        $last_closed = $this->caja_model->get_last_closed_caja();
        $cajas = $this->caja_model->get_cajas('all', 'all');

        $this->render_view(__DIR__ . '/../Views/index.php', [
            'open_caja'   => $open_caja,
            'last_closed' => $last_closed,
            'cajas'       => $cajas
        ]);
    }

    /**
     * Endpoint API/POST para abrir una caja.
     */
    public function open()
    {
        $this->check_access('caja', $this->is_ajax());

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond_error('Método no permitido', 405);
            return;
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->verify_csrf($csrf)) {
            $this->respond_error('Token de seguridad inválido. Recargue la página.');
            return;
        }

        $rawAmount = str_replace(',', '.', trim($_POST['monto_apertura'] ?? ''));
        if (!is_numeric($rawAmount) || (float)$rawAmount < 0) {
            $this->respond_error('Ingrese un monto de apertura válido (número positivo).');
            return;
        }

        $monto_apertura = (float)$rawAmount;
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $newId = $this->caja_model->open_caja($monto_apertura, $userId);
        if ($newId === false) {
            $this->respond_error('Ya existe una caja abierta en el sistema. Debe cerrarla antes de abrir una nueva.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        audit_log($this->conn, 'CAJA_OPEN', $userId, 'caja', $newId, "Caja #$newId abierta con monto: $" . number_format($monto_apertura, 2));

        if ($this->is_ajax()) {
            $caja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => "Caja abierta con $" . number_format($monto_apertura, 2), 'caja' => $caja]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Caja abierta correctamente."));
    }

    /**
     * Registra un movimiento de ingreso o egreso en la caja abierta.
     */
    public function add_movement()
    {
        $this->check_access('caja', $this->is_ajax());

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond_error('Método no permitido', 405);
            return;
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->verify_csrf($csrf)) {
            $this->respond_error('Token de seguridad inválido.');
            return;
        }

        $open_caja = $this->caja_model->get_open_caja();
        if (!$open_caja) {
            $this->respond_error('No hay ninguna caja abierta actualmente.');
            return;
        }

        $tipo = trim($_POST['tipo'] ?? 'in');
        $concepto = trim($_POST['concepto'] ?? '');
        $detalle = trim($_POST['detalle'] ?? '');
        $rawAmount = str_replace(',', '.', trim($_POST['monto'] ?? ''));

        if (!in_array($tipo, ['in', 'out'], true)) {
            $this->respond_error('Tipo de movimiento inválido.');
            return;
        }

        if (empty($concepto)) {
            $this->respond_error('El concepto es obligatorio.');
            return;
        }

        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            $this->respond_error('El monto debe ser un valor mayor a cero.');
            return;
        }

        $monto = (float)$rawAmount;
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $id_mov = $this->caja_model->add_movement((int)$open_caja['id_caja'], $tipo, $concepto, $detalle, $monto, $userId);
        if (!$id_mov) {
            $this->respond_error('Error al registrar el movimiento.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        $tipoLabel = ($tipo === 'in') ? 'Ingreso' : 'Egreso';
        audit_log($this->conn, 'CAJA_MOV_ADD', $userId, 'caja_movimiento', $id_mov, "$tipoLabel en caja #{$open_caja['id_caja']}: $concepto (\$$monto)");

        if ($this->is_ajax()) {
            $updatedCaja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "$tipoLabel de $" . number_format($monto, 2) . " registrado.",
                'caja'    => $updatedCaja
            ]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Movimiento registrado correctamente."));
    }

    /**
     * Elimina un movimiento de la caja abierta.
     */
    public function remove_movement()
    {
        $this->check_access('caja', $this->is_ajax());

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond_error('Método no permitido', 405);
            return;
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->verify_csrf($csrf)) {
            $this->respond_error('Token de seguridad inválido.');
            return;
        }

        $open_caja = $this->caja_model->get_open_caja();
        if (!$open_caja) {
            $this->respond_error('No hay caja abierta.');
            return;
        }

        $id_mov = (int)($_POST['id_movimiento'] ?? 0);
        if ($id_mov <= 0) {
            $this->respond_error('ID de movimiento inválido.');
            return;
        }

        $ok = $this->caja_model->delete_movement($id_mov, (int)$open_caja['id_caja']);
        if (!$ok) {
            $this->respond_error('No se pudo eliminar el movimiento.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        $userId = (int)($_SESSION['user_id'] ?? 0);
        audit_log($this->conn, 'CAJA_MOV_DEL', $userId, 'caja_movimiento', $id_mov, "Movimiento eliminado en caja #{$open_caja['id_caja']}");

        if ($this->is_ajax()) {
            $updatedCaja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Movimiento eliminado correctamente.',
                'caja'    => $updatedCaja
            ]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Movimiento eliminado."));
    }

    /**
     * Cierra la caja abierta.
     */
    public function close()
    {
        $this->check_access('caja', $this->is_ajax());

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond_error('Método no permitido', 405);
            return;
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->verify_csrf($csrf)) {
            $this->respond_error('Token de seguridad inválido.');
            return;
        }

        $open_caja = $this->caja_model->get_open_caja();
        if (!$open_caja) {
            $this->respond_error('No hay ninguna caja abierta para cerrar.');
            return;
        }

        $rawAmount = str_replace(',', '.', trim($_POST['monto_cierre'] ?? ''));
        if (!is_numeric($rawAmount) || (float)$rawAmount < 0) {
            $this->respond_error('Ingrese un monto de cierre válido (efectivo contado).');
            return;
        }

        $monto_cierre = (float)$rawAmount;
        $obs = trim($_POST['observaciones'] ?? '');
        $esperado = (float)$open_caja['monto_esperado'];
        $diff = $monto_cierre - $esperado;

        if (abs($diff) >= 0.01 && empty($obs)) {
            $this->respond_error('Debe ingresar una observación explicando la diferencia para poder cerrar.');
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $ok = $this->caja_model->close_caja((int)$open_caja['id_caja'], $monto_cierre, $obs, $userId);

        if (!$ok) {
            $this->respond_error('Error al cerrar la caja en la base de datos.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        audit_log($this->conn, 'CAJA_CLOSE', $userId, 'caja', $open_caja['id_caja'], "Caja #{$open_caja['id_caja']} cerrada con: $" . number_format($monto_cierre, 2) . " (Dif: $" . number_format($diff, 2) . ")");

        if ($this->is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => abs($diff) < 0.01 ? "Caja cerrada con $" . number_format($monto_cierre, 2) . ". Cuadra." : "Caja cerrada con $" . number_format($monto_cierre, 2) . ". Diferencia: $" . number_format($diff, 2),
                'diferencia' => $diff
            ]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Caja cerrada correctamente."));
    }

    /**
     * Endpoint API para consultar el detalle de una caja específica (para modal detalle).
     */
    public function detail()
    {
        $this->check_access('caja', true);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->respond_error('ID de caja inválido', 400);
            return;
        }

        $caja = $this->caja_model->get_caja_by_id($id);
        if (!$caja) {
            $this->respond_error('Caja no encontrada', 404);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'caja' => $caja]);
        exit;
    }

    /**
     * Exporta el historial de cajas a CSV (con codificación UTF-8 y BOM para Excel).
     */
    public function export_csv()
    {
        $this->check_access('caja');

        $status = $_GET['status'] ?? 'all';
        $month = $_GET['month'] ?? 'all';
        $list = $this->caja_model->get_cajas($status, $month);

        $filename = "cajas-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Fecha',
            'Hora apertura',
            'Hora cierre',
            'Monto de apertura',
            'Ingresos',
            'Egresos',
            'Esperado',
            'Monto de cierre',
            'Diferencia',
            'Estado',
            'Observación',
            'Usuario apertura',
            'Usuario cierre'
        ]);

        foreach ($list as $c) {
            $fdate = date('d/m/Y', strtotime($c['fecha']));
            $horaAp = $c['hora_apertura'] ?: '';
            $horaCi = $c['hora_cierre'] ?: '';
            $apertura = number_format((float)$c['monto_apertura'], 2, '.', '');
            $ingresos = number_format((float)($c['ingresos'] ?? 0), 2, '.', '');
            $egresos = number_format((float)($c['egresos'] ?? 0), 2, '.', '');
            $esperado = number_format((float)($c['monto_esperado'] ?? 0), 2, '.', '');
            $cierre = ($c['monto_cierre'] !== null) ? number_format((float)$c['monto_cierre'], 2, '.', '') : '';
            $diferencia = ($c['diferencia'] !== null) ? number_format((float)$c['diferencia'], 2, '.', '') : '';
            $estado = ($c['estado'] === 'abierta') ? 'Abierta' : 'Cerrada';
            $obs = $c['observaciones'] ?: '';
            $uAp = $c['usuario_apertura'] ?: '';
            $uCi = $c['usuario_cierre'] ?: '';

            fputcsv($output, [
                $fdate,
                $horaAp,
                $horaCi,
                $apertura,
                $ingresos,
                $egresos,
                $esperado,
                $cierre,
                $diferencia,
                $estado,
                $obs,
                $uAp,
                $uCi
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Restablece los datos de demo.
     */
    public function reset_demo()
    {
        $this->check_access('caja', $this->is_ajax());

        $userId = (int)($_SESSION['user_id'] ?? 11);
        $this->caja_model->reset_demo_data($userId);

        require_once __DIR__ . '/../../../config/audit.php';
        audit_log($this->conn, 'CAJA_RESET_DEMO', $userId, 'caja', 0, "Datos de demo de caja restablecidos.");

        if ($this->is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Datos de demo restablecidos con éxito.']);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Datos de demo restablecidos con éxito."));
    }

    private function is_ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || isset($_POST['ajax']) || isset($_GET['ajax']);
    }

    private function respond_error(string $message, int $code = 400): void
    {
        if ($this->is_ajax()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message]);
            exit;
        }
        $this->redirect('index.php?error=' . urlencode($message));
    }
}

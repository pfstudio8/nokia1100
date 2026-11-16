<?php
// modules/caja/Controllers/CajaController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../../../classes/Layout.php';
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
     * Verifica si el usuario actual posee rol de Administrador o permiso explícito de supervisión/cierre de caja.
     */
    private function has_supervisor_permission(): bool
    {
        if (($_SESSION['role'] ?? '') === 'admin') {
            return true;
        }
        return Layout::hasAccess('cierre_caja') || Layout::hasAccess('caja_admin');
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
            'open_caja'     => $open_caja,
            'last_closed'   => $last_closed,
            'cajas'         => $cajas,
            'can_supervise' => $this->has_supervisor_permission(),
            'limite_retiro' => CajaModel::LIMITE_RETIRO_EMPLEADO
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

        $monto_apertura = round((float)$rawAmount, 2);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $newId = $this->caja_model->open_caja($monto_apertura, $userId);
        if ($newId === false) {
            $this->respond_error('Ya existe una caja abierta en el sistema. Debe cerrarla antes de abrir una nueva.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        audit_log($this->conn, 'CAJA_OPEN', $userId, 'caja', $newId, "Caja #$newId abierta con monto inicial en efectivo: $" . number_format($monto_apertura, 2));

        if ($this->is_ajax()) {
            $caja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => "Caja abierta con $" . number_format($monto_apertura, 2), 'caja' => $caja]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Caja abierta correctamente."));
    }

    /**
     * Registra un movimiento de ingreso o egreso en el libro inmutable de la caja abierta.
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
        $medio_pago = trim($_POST['medio_pago'] ?? 'Efectivo');
        $motivo = trim($_POST['motivo'] ?? '');
        $rawAmount = str_replace(',', '.', trim($_POST['monto'] ?? ''));

        if (!in_array($tipo, ['in', 'out'], true)) {
            $this->respond_error('Tipo de movimiento inválido.');
            return;
        }

        if (!in_array($medio_pago, CajaModel::MEDIOS_PAGO, true)) {
            $medio_pago = 'Efectivo';
        }

        if (empty($concepto)) {
            $this->respond_error('El concepto es obligatorio.');
            return;
        }

        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            $this->respond_error('El monto debe ser un valor mayor a cero.');
            return;
        }

        $monto = round((float)$rawAmount, 2);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        // Control de rol/permiso para retiros o egresos grandes
        if ($tipo === 'out' && $monto >= CajaModel::LIMITE_RETIRO_EMPLEADO && !$this->has_supervisor_permission()) {
            $limiteFmt = number_format(CajaModel::LIMITE_RETIRO_EMPLEADO, 2, ',', '.');
            $this->respond_error("Los retiros o egresos mayores o iguales a \$$limiteFmt requieren rol de Administrador o permiso de Supervisión de Caja.", 403);
            return;
        }

        $id_mov = $this->caja_model->add_movement(
            (int)$open_caja['id_caja'],
            $tipo,
            $concepto,
            $detalle,
            $monto,
            $userId,
            $medio_pago,
            $motivo !== '' ? $motivo : null
        );
        if (!$id_mov) {
            $this->respond_error('Error al registrar el movimiento en la caja.');
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        $tipoLabel = ($tipo === 'in') ? 'Ingreso' : 'Egreso';
        $motivoLog = $motivo !== '' ? " | Motivo: $motivo" : '';
        audit_log(
            $this->conn,
            'CAJA_MOV_ADD',
            $userId,
            'caja_movimiento',
            $id_mov,
            "$tipoLabel en caja #{$open_caja['id_caja']} ($medio_pago): $concepto (\$$monto)$motivoLog"
        );

        if ($this->is_ajax()) {
            $updatedCaja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "$tipoLabel de $" . number_format($monto, 2) . " ($medio_pago) registrado.",
                'caja'    => $updatedCaja
            ]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Movimiento registrado correctamente."));
    }

    /**
     * Registra un contra-asiento (movimiento inverso con motivo obligatorio) en lugar de borrar filas.
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

        if (!$this->has_supervisor_permission()) {
            $this->respond_error('Solo un Administrador o usuario con permiso de Supervisión de Caja puede registrar contra-asientos.', 403);
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

        $motivo = trim($_POST['motivo'] ?? '');
        if ($motivo === '') {
            $this->respond_error('Debe ingresar un motivo obligatorio para registrar el contra-asiento.');
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $rev = $this->caja_model->reverse_movement($id_mov, (int)$open_caja['id_caja'], $motivo, $userId);
        } catch (Exception $e) {
            $this->respond_error($e->getMessage());
            return;
        }

        require_once __DIR__ . '/../../../config/audit.php';
        $orig = $rev['original'];
        $idContra = $rev['id_contra_asiento'];
        audit_log(
            $this->conn,
            'CAJA_MOV_REVERSE',
            $userId,
            'caja_movimiento',
            $idContra,
            "Contra-asiento #$idContra de mov. #$id_mov en caja #{$open_caja['id_caja']} | Concepto orig.: {$orig['concepto']} | Medio: {$orig['medio_pago']} | Monto: \${$orig['monto']} | Motivo: $motivo"
        );

        if ($this->is_ajax()) {
            $updatedCaja = $this->caja_model->get_open_caja();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Contra-asiento registrado correctamente en el libro de caja.',
                'caja'    => $updatedCaja
            ]);
            exit;
        }

        $this->redirect('index.php?success=' . urlencode("Contra-asiento registrado."));
    }

    /**
     * Cierra la caja abierta bajo modalidad de Cierre Ciego (el esperado y la diferencia se revelan al confirmar).
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

        if (!$this->has_supervisor_permission()) {
            $this->respond_error('Solo un Administrador o usuario con permiso de Supervisión de Caja puede realizar el cierre de caja.', 403);
            return;
        }

        $open_caja = $this->caja_model->get_open_caja();
        if (!$open_caja) {
            $this->respond_error('No hay ninguna caja abierta para cerrar.');
            return;
        }

        $id_caja = (int)$open_caja['id_caja'];
        $lockKey = 'blind_close_locked_amount_' . $id_caja;

        $rawAmount = str_replace(',', '.', trim($_POST['monto_cierre'] ?? ''));
        if (!is_numeric($rawAmount) || (float)$rawAmount < 0) {
            $this->respond_error('Ingrese un monto de cierre válido (efectivo físico contado).');
            return;
        }

        // Si el monto ya fue bloqueado en el primer conteo ciego de esta sesión, impedir que se altere tras conocer la diferencia
        if (isset($_SESSION[$lockKey]) && is_numeric($_SESSION[$lockKey])) {
            $monto_cierre = round((float)$_SESSION[$lockKey], 2);
        } else {
            $monto_cierre = round((float)$rawAmount, 2);
        }

        $obs = trim($_POST['observaciones'] ?? '');
        $esperado = round((float)$open_caja['monto_esperado'], 2);
        $diff = round($monto_cierre - $esperado, 2);

        // Si hay diferencia y aún no se ingresó observación: bloquear el monto contado y revelar la diferencia exigiendo observación
        if (abs($diff) >= 0.01 && $obs === '') {
            $_SESSION[$lockKey] = $monto_cierre;
            if ($this->is_ajax()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'        => false,
                    'require_obs'    => true,
                    'monto_apertura' => (float)$open_caja['monto_apertura'],
                    'ingresos'       => (float)$open_caja['ingresos'],
                    'egresos'        => (float)$open_caja['egresos'],
                    'monto_esperado' => $esperado,
                    'monto_cierre'   => $monto_cierre,
                    'diferencia'     => $diff,
                    'error'          => 'Se detectó una diferencia en el conteo ciego. El monto contado quedó fijado; ingrese una observación obligatoria para confirmar el cierre.'
                ]);
                exit;
            }
            $this->respond_error('Existe una diferencia de $' . number_format($diff, 2) . ' respecto al esperado ($' . number_format($esperado, 2) . '). Debe ingresar una observación explicando la diferencia.');
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = $this->caja_model->close_caja($id_caja, $monto_cierre, $obs, $userId);

        if ($result === false) {
            $this->respond_error('Error al cerrar la caja en la base de datos.');
            return;
        }

        unset($_SESSION[$lockKey]);

        require_once __DIR__ . '/../../../config/audit.php';
        $esperadoFinal = $result['monto_esperado'];
        $diffFinal = $result['diferencia'];
        $obsLog = $obs !== '' ? " | Obs: $obs" : '';
        audit_log(
            $this->conn,
            'CAJA_CLOSE',
            $userId,
            'caja',
            $id_caja,
            "Caja #$id_caja cerrada (cierre ciego) | Esperado: $" . number_format($esperadoFinal, 2) . " | Contado: $" . number_format($monto_cierre, 2) . " | Dif: $" . number_format($diffFinal, 2) . $obsLog
        );

        if ($this->is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            $cuadra = abs($diffFinal) < 0.01;
            echo json_encode([
                'success'        => true,
                'message'        => $cuadra
                    ? "Caja cerrada con $" . number_format($monto_cierre, 2) . ". El arqueo cuadra con exactitud."
                    : "Caja cerrada con $" . number_format($monto_cierre, 2) . ". Diferencia registrada: $" . number_format($diffFinal, 2),
                'monto_apertura' => $result['monto_apertura'],
                'ingresos'       => $result['ingresos'],
                'egresos'        => $result['egresos'],
                'monto_esperado' => $esperadoFinal,
                'monto_cierre'   => $monto_cierre,
                'diferencia'     => $diffFinal,
                'cuadra'         => $cuadra
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

        // En una caja abierta, ocultar el efectivo esperado del payload para preservar el cierre ciego
        if (($caja['estado'] ?? '') === 'abierta') {
            $caja['monto_esperado'] = null;
            $caja['ingresos'] = null;
            $caja['egresos'] = null;
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
            'Ingresos (Efectivo)',
            'Egresos (Efectivo)',
            'Esperado (Efectivo)',
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

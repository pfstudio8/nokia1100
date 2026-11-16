<?php
// modules/caja/Models/CajaModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class CajaModel extends BaseModel
{
    /**
     * Medios de pago admitidos en el libro de caja.
     */
    public const MEDIOS_PAGO = ['Efectivo', 'Transferencia', 'Tarjeta'];

    /**
     * Límite máximo para retiros/egresos manuales sin rol Administrador / Supervisor de caja.
     */
    public const LIMITE_RETIRO_EMPLEADO = 50000.00;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene la caja actualmente abierta si existe, junto con su libro de movimientos.
     * Mientras está abierta, el monto esperado se calcula siempre desde los movimientos en efectivo.
     */
    public function get_open_caja(): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM caja WHERE estado = 'abierta' ORDER BY id_caja DESC LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();
        $caja = $res->fetch_assoc();
        $stmt->close();

        if (!$caja) {
            return null;
        }

        $id_caja = (int)$caja['id_caja'];
        $caja['movs'] = $this->get_movements($id_caja);
        $totals = $this->calculate_totals($id_caja, (float)$caja['monto_apertura']);
        $caja['ingresos'] = $totals['ingresos'];
        $caja['egresos'] = $totals['egresos'];
        $caja['monto_esperado'] = $totals['esperado'];
        $caja['otros_medios'] = $totals['otros_medios'];

        return $caja;
    }

    /**
     * Obtiene la última caja cerrada.
     */
    public function get_last_closed_caja(): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM caja WHERE estado = 'cerrada' ORDER BY fecha DESC, id_caja DESC LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();
        $caja = $res->fetch_assoc();
        $stmt->close();
        return $caja ?: null;
    }

    /**
     * Obtiene el listado de cajas según filtros de estado y mes.
     * En cajas abiertas calcula el esperado en vivo; en cerradas respeta el valor congelado al cierre.
     */
    public function get_cajas(string $status = 'all', string $month = 'all'): array
    {
        $conditions = [];
        $params = [];
        $types = '';

        if ($status === 'open') {
            $conditions[] = "c.estado = 'abierta'";
        } elseif ($status === 'closed') {
            $conditions[] = "c.estado = 'cerrada'";
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $conditions[] = "DATE_FORMAT(c.fecha, '%Y-%m') = ?";
            $params[] = $month;
            $types .= 's';
        }

        $sql = "SELECT c.*, 
                       u_ap.nombre_usuario AS usuario_apertura,
                       u_ci.nombre_usuario AS usuario_cierre
                FROM caja c
                LEFT JOIN usuario u_ap ON c.id_usuario_apertura = u_ap.id_usuario
                LEFT JOIN usuario u_ci ON c.id_usuario_cierre = u_ci.id_usuario";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.fecha DESC, c.id_caja DESC";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $totals = $this->calculate_totals((int)$row['id_caja'], (float)$row['monto_apertura']);
            $row['ingresos'] = $totals['ingresos'];
            $row['egresos'] = $totals['egresos'];
            $row['otros_medios'] = $totals['otros_medios'];

            if ($row['estado'] === 'cerrada') {
                $esperadoCongelado = ($row['monto_esperado'] !== null) ? (float)$row['monto_esperado'] : $totals['esperado'];
                $row['monto_esperado'] = $esperadoCongelado;
                $row['diferencia'] = ($row['monto_cierre'] !== null)
                    ? round((float)$row['monto_cierre'] - $esperadoCongelado, 2)
                    : null;
            } else {
                $row['monto_esperado'] = $totals['esperado'];
                $row['diferencia'] = null;
            }
            $list[] = $row;
        }
        $stmt->close();
        return $list;
    }

    /**
     * Obtiene una caja puntual por ID con todos sus movimientos y totales.
     */
    public function get_caja_by_id(int $id_caja): ?array
    {
        $stmt = $this->conn->prepare("SELECT c.*, 
                                             u_ap.nombre_usuario AS usuario_apertura,
                                             u_ci.nombre_usuario AS usuario_cierre
                                      FROM caja c
                                      LEFT JOIN usuario u_ap ON c.id_usuario_apertura = u_ap.id_usuario
                                      LEFT JOIN usuario u_ci ON c.id_usuario_cierre = u_ci.id_usuario
                                      WHERE c.id_caja = ?");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $res = $stmt->get_result();
        $caja = $res->fetch_assoc();
        $stmt->close();

        if (!$caja) {
            return null;
        }

        $caja['movs'] = $this->get_movements($id_caja);
        $totals = $this->calculate_totals($id_caja, (float)$caja['monto_apertura']);
        $caja['ingresos'] = $totals['ingresos'];
        $caja['egresos'] = $totals['egresos'];
        $caja['otros_medios'] = $totals['otros_medios'];

        if ($caja['estado'] === 'cerrada') {
            $esperadoCongelado = ($caja['monto_esperado'] !== null) ? (float)$caja['monto_esperado'] : $totals['esperado'];
            $caja['monto_esperado'] = $esperadoCongelado;
            $caja['diferencia'] = ($caja['monto_cierre'] !== null)
                ? round((float)$caja['monto_cierre'] - $esperadoCongelado, 2)
                : null;
        } else {
            $caja['monto_esperado'] = $totals['esperado'];
            $caja['diferencia'] = null;
        }

        return $caja;
    }

    /**
     * Libro inmutable de movimientos asociados a una caja, incluyendo trazabilidad de contra-asientos y origen.
     */
    public function get_movements(int $id_caja): array
    {
        $sql = "SELECT m.*,
                       COALESCE(NULLIF(TRIM(m.medio_pago), ''), 'Efectivo') AS medio_pago,
                       DATE_FORMAT(m.fecha_creacion, '%d/%m/%Y %H:%i:%s') AS fecha_hora_exacta,
                       COALESCE(NULLIF(TRIM(m.hora), ''), DATE_FORMAT(m.fecha_creacion, '%H:%i')) AS hora,
                       u.nombre_usuario,
                       (SELECT COUNT(*) FROM caja_movimiento rev WHERE rev.id_movimiento_origen = m.id_movimiento) AS ya_revertido
                FROM caja_movimiento m
                LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
                WHERE m.id_caja = ?
                ORDER BY m.id_movimiento DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $res = $stmt->get_result();
        $movs = [];
        while ($r = $res->fetch_assoc()) {
            $r['ya_revertido'] = ((int)($r['ya_revertido'] ?? 0)) > 0;
            $movs[] = $r;
        }
        $stmt->close();
        return $movs;
    }

    /**
     * Calcula ingresos, egresos y efectivo físico esperado filtrando estrictamente `medio_pago = 'Efectivo'`.
     * También devuelve el resumen de otros medios registrados en el libro para el panorama completo del día.
     */
    public function calculate_totals(int $id_caja, float $apertura): array
    {
        $stmt = $this->conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'in' AND LOWER(TRIM(COALESCE(medio_pago, 'Efectivo'))) = 'efectivo' THEN monto ELSE 0 END), 0) AS ingresos_efectivo,
            COALESCE(SUM(CASE WHEN tipo = 'out' AND LOWER(TRIM(COALESCE(medio_pago, 'Efectivo'))) = 'efectivo' THEN monto ELSE 0 END), 0) AS egresos_efectivo,
            COALESCE(SUM(CASE WHEN LOWER(TRIM(COALESCE(medio_pago, ''))) LIKE '%transf%' THEN (CASE WHEN tipo = 'in' THEN monto ELSE -monto END) ELSE 0 END), 0) AS neto_transferencia,
            COALESCE(SUM(CASE WHEN LOWER(TRIM(COALESCE(medio_pago, ''))) LIKE '%tarjeta%' THEN (CASE WHEN tipo = 'in' THEN monto ELSE -monto END) ELSE 0 END), 0) AS neto_tarjeta
            FROM caja_movimiento WHERE id_caja = ?");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ingresos = round((float)($res['ingresos_efectivo'] ?? 0), 2);
        $egresos = round((float)($res['egresos_efectivo'] ?? 0), 2);
        $esperado = round($apertura + $ingresos - $egresos, 2);

        $transf = max(0.0, round((float)($res['neto_transferencia'] ?? 0), 2));
        $tarjeta = max(0.0, round((float)($res['neto_tarjeta'] ?? 0), 2));

        return [
            'ingresos' => $ingresos,
            'egresos'  => $egresos,
            'esperado' => $esperado,
            'otros_medios' => [
                'transferencia'     => $transf,
                'tarjeta'           => $tarjeta,
                'total_no_efectivo' => round($transf + $tarjeta, 2),
                'efectivo_ventas'   => $ingresos
            ]
        ];
    }

    /**
     * Abre una nueva caja en una transacción atómica con bloqueo de concurrencia.
     * `monto_esperado` queda en NULL mientras está abierta (se calcula siempre desde los movimientos).
     */
    public function open_caja(float $monto_apertura, int $id_usuario, ?string $fecha = null, ?string $hora = null): int|false
    {
        $this->conn->begin_transaction();
        try {
            $stmtLock = $this->conn->prepare("SELECT id_caja FROM caja WHERE estado = 'abierta' LIMIT 1 FOR UPDATE");
            $stmtLock->execute();
            $existing = $stmtLock->get_result()->fetch_assoc();
            $stmtLock->close();

            if ($existing) {
                $this->conn->rollback();
                return false;
            }

            $fecha = $fecha ?: date('Y-m-d');
            $hora = $hora ?: date('H:i');
            $uid = ($id_usuario > 0) ? $id_usuario : null;

            $stmt = $this->conn->prepare("INSERT INTO caja (fecha, hora_apertura, monto_apertura, monto_esperado, estado, id_usuario_apertura) 
                                          VALUES (?, ?, ?, NULL, 'abierta', ?)");
            $stmt->bind_param("ssdi", $fecha, $hora, $monto_apertura, $uid);
            if (!$stmt->execute()) {
                throw new Exception("No se pudo insertar la apertura de caja.");
            }
            $newId = (int)$stmt->insert_id;
            $stmt->close();

            $this->conn->commit();
            return $newId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Agrega un movimiento manual al libro inmutable dentro de una transacción con bloqueo FOR UPDATE.
     */
    public function add_movement(
        int $id_caja,
        string $tipo,
        string $concepto,
        ?string $detalle,
        float $monto,
        int $id_usuario,
        string $medio_pago = 'Efectivo',
        ?string $motivo = null,
        ?string $hora = null
    ): int|false {
        $tipo = ($tipo === 'out' || $tipo === 'egreso') ? 'out' : 'in';
        $hora = $hora ?: date('H:i');
        $fecha_creacion = date('Y-m-d H:i:s');
        $detalle = $detalle ? trim($detalle) : '';
        $motivo = $motivo ? trim($motivo) : null;
        $medio_pago = trim($medio_pago) ?: 'Efectivo';
        $uid = ($id_usuario > 0) ? $id_usuario : null;
        $monto = round($monto, 2);

        $this->conn->begin_transaction();
        try {
            // Bloquear la fila de la caja para asegurar que permanezca abierta durante la inserción
            $stmtLock = $this->conn->prepare("SELECT id_caja FROM caja WHERE id_caja = ? AND estado = 'abierta' FOR UPDATE");
            $stmtLock->bind_param("i", $id_caja);
            $stmtLock->execute();
            $cajaRow = $stmtLock->get_result()->fetch_assoc();
            $stmtLock->close();

            if (!$cajaRow) {
                $this->conn->rollback();
                return false;
            }

            $stmt = $this->conn->prepare("INSERT INTO caja_movimiento 
                (id_caja, tipo, medio_pago, concepto, detalle, motivo, monto, hora, id_usuario, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssdsis", $id_caja, $tipo, $medio_pago, $concepto, $detalle, $motivo, $monto, $hora, $uid, $fecha_creacion);
            if (!$stmt->execute()) {
                throw new Exception("No se pudo registrar el movimiento.");
            }
            $id_mov = (int)$stmt->insert_id;
            $stmt->close();

            $this->conn->commit();
            return $id_mov;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Libro inmutable: no borra movimientos. Registra un contra-asiento (movimiento inverso) con motivo obligatorio
     * dentro de una transacción atómica con FOR UPDATE.
     *
     * @throws Exception Si el movimiento no puede revertirse.
     */
    public function reverse_movement(int $id_movimiento, int $id_caja, string $motivo, int $id_usuario): array
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new Exception("Debe indicar un motivo obligatorio para registrar el contra-asiento.");
        }

        $this->conn->begin_transaction();
        try {
            $stmtCaja = $this->conn->prepare("SELECT id_caja FROM caja WHERE id_caja = ? AND estado = 'abierta' FOR UPDATE");
            $stmtCaja->bind_param("i", $id_caja);
            $stmtCaja->execute();
            $openCaja = $stmtCaja->get_result()->fetch_assoc();
            $stmtCaja->close();

            if (!$openCaja) {
                throw new Exception("La caja no se encuentra abierta.");
            }

            $stmtMov = $this->conn->prepare("SELECT * FROM caja_movimiento WHERE id_movimiento = ? AND id_caja = ? FOR UPDATE");
            $stmtMov->bind_param("ii", $id_movimiento, $id_caja);
            $stmtMov->execute();
            $orig = $stmtMov->get_result()->fetch_assoc();
            $stmtMov->close();

            if (!$orig) {
                throw new Exception("El movimiento indicado no existe en esta caja.");
            }

            if (!empty($orig['id_movimiento_origen'])) {
                throw new Exception("No se puede revertir un movimiento que ya es un contra-asiento.");
            }

            if (!empty($orig['id_venta']) || !empty($orig['id_reparacion'])) {
                throw new Exception("Este movimiento proviene de una venta o reparación (#TX-" . str_pad((string)($orig['id_venta'] ?? 0), 5, '0', STR_PAD_LEFT) . "). Para revertirlo, anule la venta desde el módulo de Ventas.");
            }

            // Verificar que no haya sido revertido previamente
            $stmtCheck = $this->conn->prepare("SELECT id_movimiento FROM caja_movimiento WHERE id_movimiento_origen = ? FOR UPDATE");
            $stmtCheck->bind_param("i", $id_movimiento);
            $stmtCheck->execute();
            $alreadyReversed = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if ($alreadyReversed) {
                throw new Exception("Este movimiento ya fue anulado mediante el contra-asiento #" . $alreadyReversed['id_movimiento'] . ".");
            }

            $nuevoTipo = ($orig['tipo'] === 'in') ? 'out' : 'in';
            $medioPago = trim((string)($orig['medio_pago'] ?? 'Efectivo')) ?: 'Efectivo';
            $nuevoConcepto = mb_substr("Contra-asiento: " . $orig['concepto'], 0, 100);
            $nuevoDetalle = mb_substr("Ajuste inverso de mov. #{$id_movimiento} · Motivo: {$motivo}", 0, 255);
            $monto = round((float)$orig['monto'], 2);
            $hora = date('H:i');
            $fecha_creacion = date('Y-m-d H:i:s');
            $uid = ($id_usuario > 0) ? $id_usuario : null;

            $stmtIns = $this->conn->prepare("INSERT INTO caja_movimiento 
                (id_caja, tipo, medio_pago, concepto, detalle, motivo, monto, id_movimiento_origen, hora, id_usuario, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->bind_param(
                "isssssdisis",
                $id_caja,
                $nuevoTipo,
                $medioPago,
                $nuevoConcepto,
                $nuevoDetalle,
                $motivo,
                $monto,
                $id_movimiento,
                $hora,
                $uid,
                $fecha_creacion
            );
            if (!$stmtIns->execute()) {
                throw new Exception("Error al insertar el contra-asiento en la base de datos.");
            }
            $id_contra = (int)$stmtIns->insert_id;
            $stmtIns->close();

            $this->conn->commit();

            return [
                'id_contra_asiento' => $id_contra,
                'original'          => $orig,
                'nuevo_tipo'        => $nuevoTipo,
                'monto'             => $monto,
                'concepto'          => $nuevoConcepto,
                'motivo'            => $motivo
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Cierra una caja abierta de forma atómica con bloqueo FOR UPDATE (Cierre Ciego).
     * Calcula y congela `monto_esperado` y `diferencia` recién en el instante del cierre.
     *
     * @return array|false Datos del arqueo cerrado o false en caso de error.
     */
    public function close_caja(int $id_caja, float $monto_cierre, string $obs, int $id_usuario, ?string $hora = null): array|false
    {
        $hora = $hora ?: date('H:i');
        $monto_cierre = round($monto_cierre, 2);
        $obs = trim($obs);
        $uid = ($id_usuario > 0) ? $id_usuario : null;

        $this->conn->begin_transaction();
        try {
            // Bloquear fila de la caja para impedir movimientos concurrentes o doble cierre
            $stmtLock = $this->conn->prepare("SELECT * FROM caja WHERE id_caja = ? AND estado = 'abierta' FOR UPDATE");
            $stmtLock->bind_param("i", $id_caja);
            $stmtLock->execute();
            $caja = $stmtLock->get_result()->fetch_assoc();
            $stmtLock->close();

            if (!$caja) {
                $this->conn->rollback();
                return false;
            }

            // Calcular el efectivo esperado real dentro de la transacción bloqueada y congelarlo al cerrar
            $totals = $this->calculate_totals($id_caja, (float)$caja['monto_apertura']);
            $esperado = $totals['esperado'];
            $diferencia = round($monto_cierre - $esperado, 2);

            $stmt = $this->conn->prepare("UPDATE caja SET 
                                            monto_cierre = ?,
                                            monto_esperado = ?,
                                            diferencia = ?,
                                            hora_cierre = ?,
                                            estado = 'cerrada',
                                            observaciones = ?,
                                            id_usuario_cierre = ?
                                          WHERE id_caja = ? AND estado = 'abierta'");
            $stmt->bind_param("dddssii", $monto_cierre, $esperado, $diferencia, $hora, $obs, $uid, $id_caja);
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                $stmt->close();
                throw new Exception("No se pudo actualizar el cierre de caja.");
            }
            $stmt->close();

            $this->conn->commit();

            return [
                'id_caja'        => $id_caja,
                'monto_apertura' => (float)$caja['monto_apertura'],
                'ingresos'       => $totals['ingresos'],
                'egresos'        => $totals['egresos'],
                'monto_esperado' => $esperado,
                'monto_cierre'   => $monto_cierre,
                'diferencia'     => $diferencia,
                'otros_medios'   => $totals['otros_medios']
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Registra en el libro inmutable de caja los pagos de una venta o reparación DENTRO de la misma transacción.
     * - Si la venta incluye Efectivo (`$monto_efectivo > 0`) y NO hay caja abierta, lanza Exception (bloquea la venta).
     * - Registra cada pago con su `medio_pago`, `id_venta` e `id_reparacion`.
     * - El efectivo esperado de la caja física se calcula filtrando `medio_pago = 'Efectivo'`.
     *
     * @param array<int, array{metodo: string, monto: float}> $pagos_validados
     * @throws Exception Si hay cobro en efectivo sin caja abierta o si falla la inserción en el libro.
     */
    public static function recordSaleMovementsInTransaction(
        mysqli $conn,
        int $id_venta,
        ?int $id_reparacion,
        array $pagos_validados,
        float $monto_efectivo,
        float $total_venta,
        bool $es_combinado,
        string $conceptoBase,
        string $detalleExtra = '',
        ?int $id_usuario = null
    ): void {
        // Bloquear la caja abierta dentro de la transacción de venta
        $stmtCaja = $conn->prepare("SELECT id_caja FROM caja WHERE estado = 'abierta' ORDER BY id_caja DESC LIMIT 1 FOR UPDATE");
        if (!$stmtCaja) {
            throw new Exception("Error al verificar el estado de la caja.");
        }
        $stmtCaja->execute();
        $openCaja = $stmtCaja->get_result()->fetch_assoc();
        $stmtCaja->close();

        if (!$openCaja) {
            if (round($monto_efectivo, 2) > 0) {
                throw new Exception("No hay ninguna caja abierta. Debe abrir la caja del día antes de registrar cobros en efectivo.");
            }
            // Si el cobro es 100% electrónico y la caja está cerrada, no se escribe en caja_movimiento
            return;
        }

        $id_caja = (int)$openCaja['id_caja'];

        // Evitar duplicados verificando por id_venta
        $stmtDup = $conn->prepare("SELECT id_movimiento FROM caja_movimiento WHERE id_venta = ? AND tipo = 'in' LIMIT 1");
        $stmtDup->bind_param("i", $id_venta);
        $stmtDup->execute();
        $dup = $stmtDup->get_result()->fetch_assoc();
        $stmtDup->close();
        if ($dup) {
            throw new Exception("Los movimientos de caja para la venta #$id_venta ya fueron registrados.");
        }

        $txCode = '#TX-' . str_pad((string)$id_venta, 5, '0', STR_PAD_LEFT);
        $hora = date('H:i');
        $fecha_creacion = date('Y-m-d H:i:s');
        $uid = ($id_usuario !== null && $id_usuario > 0) ? $id_usuario : null;
        $motivo = $es_combinado ? "Pago combinado ($txCode)" : "Cobro de operación ($txCode)";

        $stmtIns = $conn->prepare("INSERT INTO caja_movimiento 
            (id_caja, tipo, medio_pago, concepto, detalle, motivo, monto, id_venta, id_reparacion, hora, id_usuario, fecha_creacion) 
            VALUES (?, 'in', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($pagos_validados as $pItem) {
            $medio = trim((string)$pItem['metodo']);
            $monto = round((float)$pItem['monto'], 2);
            if ($monto <= 0) {
                continue;
            }

            if ($id_reparacion !== null && $id_reparacion > 0) {
                $concepto = 'Cobro de reparación';
            } else {
                $concepto = ($medio === 'Efectivo') ? 'Venta en efectivo' : "Venta ($medio)";
            }

            if ($es_combinado) {
                $detalle = "Venta $txCode · Pago combinado ($medio: $" . number_format($monto, 2, ',', '.') . " de $" . number_format($total_venta, 2, ',', '.') . ")";
            } else {
                $detalle = "Venta $txCode" . ($detalleExtra !== '' ? " · " . $detalleExtra : '');
            }
            $detalle = mb_substr($detalle, 0, 250);

            $stmtIns->bind_param(
                "issssdiisis",
                $id_caja,
                $medio,
                $concepto,
                $detalle,
                $motivo,
                $monto,
                $id_venta,
                $id_reparacion,
                $hora,
                $uid,
                $fecha_creacion
            );
            if (!$stmtIns->execute()) {
                $stmtIns->close();
                throw new Exception("Error al registrar el movimiento de caja para el medio $medio.");
            }
        }
        $stmtIns->close();
    }

    /**
     * Registra en el libro inmutable los contra-asientos por anulación de venta DENTRO de la misma transacción.
     * Encuentra los movimientos originales directamente por `id_venta` sin parsear texto.
     *
     * @throws Exception Si requiere revertir efectivo sin caja abierta o si falla la inserción.
     */
    public static function reverseSaleMovementsInTransaction(
        mysqli $conn,
        int $id_venta,
        float $monto_efectivo_fallback,
        ?int $id_usuario = null
    ): void {
        // Buscar movimientos originales por id_venta (sin parsear texto)
        $stmtOrig = $conn->prepare("SELECT * FROM caja_movimiento WHERE id_venta = ? AND tipo = 'in' FOR UPDATE");
        $stmtOrig->bind_param("i", $id_venta);
        $stmtOrig->execute();
        $resOrig = $stmtOrig->get_result();
        $movimientosOrigen = [];
        $requiereEfectivo = false;
        while ($row = $resOrig->fetch_assoc()) {
            $movimientosOrigen[] = $row;
            if (mb_strtolower(trim((string)($row['medio_pago'] ?? 'Efectivo')), 'UTF-8') === 'efectivo') {
                $requiereEfectivo = true;
            }
        }
        $stmtOrig->close();

        if (empty($movimientosOrigen) && round($monto_efectivo_fallback, 2) > 0) {
            $requiereEfectivo = true;
        }

        // Verificar si ya se registraron contra-asientos para esta venta
        $stmtCheck = $conn->prepare("SELECT id_movimiento FROM caja_movimiento WHERE id_venta = ? AND tipo = 'out' LIMIT 1");
        $stmtCheck->bind_param("i", $id_venta);
        $stmtCheck->execute();
        $already = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();
        if ($already) {
            return;
        }

        // Bloquear la caja abierta actual
        $stmtCaja = $conn->prepare("SELECT id_caja FROM caja WHERE estado = 'abierta' ORDER BY id_caja DESC LIMIT 1 FOR UPDATE");
        $stmtCaja->execute();
        $openCaja = $stmtCaja->get_result()->fetch_assoc();
        $stmtCaja->close();

        if (!$openCaja) {
            if ($requiereEfectivo) {
                throw new Exception("No hay ninguna caja abierta para registrar el reintegro de efectivo por la anulación de la venta.");
            }
            return;
        }

        $id_caja = (int)$openCaja['id_caja'];
        $txCode = '#TX-' . str_pad((string)$id_venta, 5, '0', STR_PAD_LEFT);
        $hora = date('H:i');
        $fecha_creacion = date('Y-m-d H:i:s');
        $uid = ($id_usuario !== null && $id_usuario > 0) ? $id_usuario : null;
        $motivo = "Anulación de Venta $txCode";

        $stmtIns = $conn->prepare("INSERT INTO caja_movimiento 
            (id_caja, tipo, medio_pago, concepto, detalle, motivo, monto, id_venta, id_reparacion, id_movimiento_origen, hora, id_usuario, fecha_creacion) 
            VALUES (?, 'out', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!empty($movimientosOrigen)) {
            foreach ($movimientosOrigen as $orig) {
                $medio = trim((string)($orig['medio_pago'] ?? 'Efectivo')) ?: 'Efectivo';
                $monto = round((float)$orig['monto'], 2);
                $id_orig = (int)$orig['id_movimiento'];
                $id_rep = !empty($orig['id_reparacion']) ? (int)$orig['id_reparacion'] : null;
                $concepto = 'Anulación de venta';
                $detalle = "Contra-asiento por anulación de Venta $txCode ($medio)";

                $stmtIns->bind_param(
                    "issssdiiisis",
                    $id_caja,
                    $medio,
                    $concepto,
                    $detalle,
                    $motivo,
                    $monto,
                    $id_venta,
                    $id_rep,
                    $id_orig,
                    $hora,
                    $uid,
                    $fecha_creacion
                );
                if (!$stmtIns->execute()) {
                    $stmtIns->close();
                    throw new Exception("Error al registrar el contra-asiento de caja por anulación de venta.");
                }
            }
        } elseif (round($monto_efectivo_fallback, 2) > 0) {
            // Fallback para ventas previas a la vinculación por id_venta
            $medio = 'Efectivo';
            $monto = round($monto_efectivo_fallback, 2);
            $concepto = 'Anulación de venta';
            $detalle = "Reintegro en efectivo por anulación de Venta $txCode";
            $nullInt = null;

            $stmtIns->bind_param(
                "issssdiiisis",
                $id_caja,
                $medio,
                $concepto,
                $detalle,
                $motivo,
                $monto,
                $id_venta,
                $nullInt,
                $nullInt,
                $hora,
                $uid,
                $fecha_creacion
            );
            if (!$stmtIns->execute()) {
                $stmtIns->close();
                throw new Exception("Error al registrar el egreso de efectivo por anulación de venta.");
            }
        }

        $stmtIns->close();
    }
}

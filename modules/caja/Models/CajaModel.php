<?php
// modules/caja/Models/CajaModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class CajaModel extends BaseModel
{
    /**
     * Obtiene la caja actualmente abierta si existe, junto con sus movimientos.
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

        $caja['movs'] = $this->get_movements((int)$caja['id_caja']);
        $totals = $this->calculate_totals((int)$caja['id_caja'], (float)$caja['monto_apertura']);
        $caja['ingresos'] = $totals['ingresos'];
        $caja['egresos'] = $totals['egresos'];
        $caja['monto_esperado'] = $totals['esperado'];

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
     */
    public function get_cajas(string $status = 'all', string $month = 'all'): array
    {
        $conditions = [];
        $params = [];
        $types = '';

        if ($status === 'open') {
            $conditions[] = "estado = 'abierta'";
        } elseif ($status === 'closed') {
            $conditions[] = "estado = 'cerrada'";
        }

        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $conditions[] = "DATE_FORMAT(fecha, '%Y-%m') = ?";
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
            $row['monto_esperado'] = $totals['esperado'];
            if ($row['estado'] === 'cerrada' && $row['monto_cierre'] !== null) {
                $row['diferencia'] = (float)$row['monto_cierre'] - $totals['esperado'];
            } else {
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
        $caja['monto_esperado'] = $totals['esperado'];
        if ($caja['estado'] === 'cerrada' && $caja['monto_cierre'] !== null) {
            $caja['diferencia'] = (float)$caja['monto_cierre'] - $totals['esperado'];
        } else {
            $caja['diferencia'] = null;
        }

        return $caja;
    }

    /**
     * Movimientos asociados a una caja.
     */
    public function get_movements(int $id_caja): array
    {
        $stmt = $this->conn->prepare("SELECT m.*, u.nombre_usuario 
                                      FROM caja_movimiento m
                                      LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
                                      WHERE m.id_caja = ?
                                      ORDER BY m.id_movimiento DESC");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $res = $stmt->get_result();
        $movs = [];
        while ($r = $res->fetch_assoc()) {
            $movs[] = $r;
        }
        $stmt->close();
        return $movs;
    }

    /**
     * Calcula ingresos, egresos y monto esperado de una caja.
     */
    public function calculate_totals(int $id_caja, float $apertura): array
    {
        $stmt = $this->conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'in' THEN monto ELSE 0 END), 0) AS ingresos,
            COALESCE(SUM(CASE WHEN tipo = 'out' THEN monto ELSE 0 END), 0) AS egresos
            FROM caja_movimiento WHERE id_caja = ?");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ingresos = (float)($res['ingresos'] ?? 0);
        $egresos = (float)($res['egresos'] ?? 0);
        $esperado = $apertura + $ingresos - $egresos;

        return [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'esperado' => $esperado
        ];
    }

    /**
     * Abre una nueva caja.
     */
    public function open_caja(float $monto_apertura, int $id_usuario, ?string $fecha = null, ?string $hora = null): int|false
    {
        // Verificar si ya hay una caja abierta
        if ($this->get_open_caja() !== null) {
            return false;
        }

        $fecha = $fecha ?: date('Y-m-d');
        $hora = $hora ?: date('H:i');
        $monto_esperado = $monto_apertura;

        $stmt = $this->conn->prepare("INSERT INTO caja (fecha, hora_apertura, monto_apertura, monto_esperado, estado, id_usuario_apertura) 
                                      VALUES (?, ?, ?, ?, 'abierta', ?)");
        $stmt->bind_param("ssddi", $fecha, $hora, $monto_apertura, $monto_esperado, $id_usuario);
        $success = $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        return $success ? $newId : false;
    }

    /**
     * Agrega un movimiento a una caja abierta y actualiza el monto esperado.
     */
    public function add_movement(int $id_caja, string $tipo, string $concepto, ?string $detalle, float $monto, int $id_usuario, ?string $hora = null): int|false
    {
        $tipo = ($tipo === 'out' || $tipo === 'egreso') ? 'out' : 'in';
        $hora = $hora ?: date('H:i');
        $detalle = $detalle ? trim($detalle) : '';

        $stmt = $this->conn->prepare("INSERT INTO caja_movimiento (id_caja, tipo, concepto, detalle, monto, hora, id_usuario) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdss", $id_caja, $tipo, $concepto, $detalle, $monto, $hora, $id_usuario);
        $success = $stmt->execute();
        $id_mov = $stmt->insert_id;
        $stmt->close();

        if ($success) {
            $this->sync_expected($id_caja);
            return $id_mov;
        }
        return false;
    }

    /**
     * Elimina un movimiento de una caja abierta y recalcula el monto esperado.
     */
    public function delete_movement(int $id_movimiento, int $id_caja): bool
    {
        // Solo permitir borrar si la caja está abierta
        $stmt = $this->conn->prepare("DELETE FROM caja_movimiento WHERE id_movimiento = ? AND id_caja = ? AND id_caja IN (SELECT id_caja FROM caja WHERE estado = 'abierta')");
        $stmt->bind_param("ii", $id_movimiento, $id_caja);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($success && $affected > 0) {
            $this->sync_expected($id_caja);
            return true;
        }
        return false;
    }

    /**
     * Cierra una caja abierta.
     */
    public function close_caja(int $id_caja, float $monto_cierre, string $obs, int $id_usuario, ?string $hora = null): bool
    {
        $hora = $hora ?: date('H:i');
        $caja = $this->get_caja_by_id($id_caja);
        if (!$caja || $caja['estado'] !== 'abierta') {
            return false;
        }

        $totals = $this->calculate_totals($id_caja, (float)$caja['monto_apertura']);
        $esperado = $totals['esperado'];
        $diferencia = $monto_cierre - $esperado;
        $obs = trim($obs);

        $stmt = $this->conn->prepare("UPDATE caja SET 
                                        monto_cierre = ?,
                                        monto_esperado = ?,
                                        diferencia = ?,
                                        hora_cierre = ?,
                                        estado = 'cerrada',
                                        observaciones = ?,
                                        id_usuario_cierre = ?
                                      WHERE id_caja = ? AND estado = 'abierta'");
        $stmt->bind_param("dddssii", $monto_cierre, $esperado, $diferencia, $hora, $obs, $id_usuario, $id_caja);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Sincroniza el monto esperado guardado en la tabla caja.
     */
    private function sync_expected(int $id_caja): void
    {
        $stmt = $this->conn->prepare("SELECT monto_apertura FROM caja WHERE id_caja = ?");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $caja = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($caja) {
            $totals = $this->calculate_totals($id_caja, (float)$caja['monto_apertura']);
            $stmtUp = $this->conn->prepare("UPDATE caja SET monto_esperado = ? WHERE id_caja = ?");
            $stmtUp->bind_param("di", $totals['esperado'], $id_caja);
            $stmtUp->execute();
            $stmtUp->close();
        }
    }

    /**
     * Restablece los datos de demo iniciales (cajas históricas).
     */
    public function reset_demo_data(int $default_user_id = 11): bool
    {
        $this->conn->query("DELETE FROM caja_movimiento");
        $this->conn->query("DELETE FROM caja");
        $this->conn->query("ALTER TABLE caja AUTO_INCREMENT = 1");
        $this->conn->query("ALTER TABLE caja_movimiento AUTO_INCREMENT = 1");

        $sql = "INSERT INTO `caja` (`id_caja`, `fecha`, `hora_apertura`, `hora_cierre`, `monto_apertura`, `monto_cierre`, `monto_esperado`, `diferencia`, `estado`, `observaciones`, `id_usuario_apertura`, `id_usuario_cierre`) VALUES
        (1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '09:10', '20:20', 12000.00, 71250.00, 71250.00, 0.00, 'cerrada', '', ?, ?),
        (2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:47', '19:55', 12000.00, 64950.00, 64800.00, 150.00, 'cerrada', 'Sobrante de $150, sin identificar.', ?, ?),
        (3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:03', '20:02', 15000.00, 102100.00, 102300.00, -200.00, 'cerrada', 'Faltante de $200 por vuelto mal dado en la tarde.', ?, ?),
        (4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:52', '20:10', 15000.00, 87450.00, 87450.00, 0.00, 'cerrada', '', ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiiiiii", $default_user_id, $default_user_id, $default_user_id, $default_user_id, $default_user_id, $default_user_id, $default_user_id, $default_user_id);
        $stmt->execute();
        $stmt->close();

        $sqlMov = "INSERT INTO `caja_movimiento` (`id_caja`, `tipo`, `concepto`, `detalle`, `monto`, `hora`, `id_usuario`) VALUES
        (1, 'in', 'Venta en efectivo', '', 64250.00, '14:30', ?),
        (1, 'in', 'Cobro de reparación', 'Flex de carga', 8000.00, '17:00', ?),
        (1, 'out', 'Gasto menor', 'Insumos limpieza', 13000.00, '11:50', ?),

        (2, 'in', 'Venta en efectivo', '', 60000.00, '15:10', ?),
        (2, 'in', 'Seña', 'Reparación placa', 9800.00, '10:25', ?),
        (2, 'out', 'Pago a proveedor', 'Accesorios Norte', 17000.00, '12:40', ?),

        (3, 'in', 'Venta en efectivo', '', 95000.00, '14:00', ?),
        (3, 'in', 'Cobro de reparación', 'Batería Moto G8', 12000.00, '18:30', ?),
        (3, 'out', 'Pago a proveedor', 'TechParts', 18700.00, '12:10', ?),
        (3, 'out', 'Retiro de efectivo', 'Depósito banco', 1000.00, '19:00', ?),

        (4, 'in', 'Venta en efectivo', 'Ventas de la mañana', 70200.00, '13:05', ?),
        (4, 'in', 'Cobro de reparación', 'Pantalla iPhone 11', 18500.00, '16:40', ?),
        (4, 'out', 'Pago a proveedor', 'DistriCell · flete', 14000.00, '11:20', ?),
        (4, 'out', 'Gasto menor', 'Librería', 2250.00, '17:15', ?)";

        $stmtM = $this->conn->prepare($sqlMov);
        $stmtM->bind_param("iiiiiiiiiiiiii", 
            $default_user_id, $default_user_id, $default_user_id,
            $default_user_id, $default_user_id, $default_user_id,
            $default_user_id, $default_user_id, $default_user_id, $default_user_id,
            $default_user_id, $default_user_id, $default_user_id, $default_user_id
        );
        $stmtM->execute();
        $stmtM->close();

        return true;
    }
}

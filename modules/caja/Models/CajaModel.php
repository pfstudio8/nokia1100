<?php
// modules/caja/Models/CajaModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class CajaModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_tables();
    }

    /**
     * Garantiza que las tablas necesarias para el módulo de caja existan en la base de datos.
     */
    private function ensure_tables(): void
    {
        $check = $this->conn->query("SHOW TABLES LIKE 'caja'");
        if ($check && $check->num_rows > 0) {
            return;
        }

        $this->conn->query("CREATE TABLE IF NOT EXISTS `caja` (
          `id_caja` int(11) NOT NULL AUTO_INCREMENT,
          `fecha` date NOT NULL,
          `hora_apertura` varchar(8) NOT NULL,
          `hora_cierre` varchar(8) DEFAULT NULL,
          `monto_apertura` decimal(12,2) NOT NULL DEFAULT 0.00,
          `monto_cierre` decimal(12,2) DEFAULT NULL,
          `monto_esperado` decimal(12,2) NOT NULL DEFAULT 0.00,
          `diferencia` decimal(12,2) DEFAULT NULL,
          `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
          `observaciones` text DEFAULT NULL,
          `id_usuario_apertura` int(11) DEFAULT NULL,
          `id_usuario_cierre` int(11) DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_caja`),
          KEY `idx_caja_estado` (`estado`),
          KEY `idx_caja_fecha` (`fecha`),
          KEY `fk_caja_usuario_apertura` (`id_usuario_apertura`),
          KEY `fk_caja_usuario_cierre` (`id_usuario_cierre`),
          CONSTRAINT `fk_caja_usuario_apertura` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
          CONSTRAINT `fk_caja_usuario_cierre` FOREIGN KEY (`id_usuario_cierre`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->query("CREATE TABLE IF NOT EXISTS `caja_movimiento` (
          `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
          `id_caja` int(11) NOT NULL,
          `tipo` enum('in','out') NOT NULL DEFAULT 'in',
          `concepto` varchar(100) NOT NULL,
          `detalle` varchar(255) DEFAULT '',
          `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
          `hora` varchar(8) NOT NULL,
          `id_usuario` int(11) DEFAULT NULL,
          `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_movimiento`),
          KEY `fk_mov_caja` (`id_caja`),
          KEY `fk_mov_usuario` (`id_usuario`),
          KEY `idx_mov_tipo` (`tipo`),
          CONSTRAINT `fk_mov_caja` FOREIGN KEY (`id_caja`) REFERENCES `caja` (`id_caja`) ON DELETE CASCADE,
          CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

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

        $id_usuario = ($id_usuario > 0) ? $id_usuario : null;

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
        $id_usuario = ($id_usuario > 0) ? $id_usuario : null;

        $stmt = $this->conn->prepare("INSERT INTO caja_movimiento (id_caja, tipo, concepto, detalle, monto, hora, id_usuario) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdsi", $id_caja, $tipo, $concepto, $detalle, $monto, $hora, $id_usuario);
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
        $id_usuario = ($id_usuario > 0) ? $id_usuario : null;

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

}

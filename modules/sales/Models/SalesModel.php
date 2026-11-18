<?php
// modules/sales/Models/SalesModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';
require_once __DIR__ . '/../../../classes/ServiceProduct.php';
require_once __DIR__ . '/../../caja/Models/CajaModel.php';

class SalesModel extends BaseModel
{
    public const METODOS_PAGO = ['Efectivo', 'Transferencia', 'Tarjeta'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Delegado de compatibilidad hacia el migrador SQL centralizado (`database/run_migrations.php`).
     */
    public static function ensure_payment_schema(mysqli $conn): void
    {
        $migFile = __DIR__ . '/../../../database/run_migrations.php';
        if (file_exists($migFile)) {
            require_once $migFile;
            apply_system_migrations($conn);
        }
    }

    /**
     * Valida y normaliza hasta 2 medios de pago combinados contra el total exacto de la venta.
     *
     * @param array  $pagos_raw       Lista de pagos [['metodo' => string, 'monto' => float], ...]
     * @param string $metodo_fallback Método único si no se envía arreglo de pagos
     * @param float  $total_esperado  Monto total exacto de la venta
     * @return array{pagos: array<int, array{metodo: string, monto: float}>, metodo_resumen: string, monto_efectivo: float, es_combinado: bool}
     */
    public static function validate_and_normalize_payments(array $pagos_raw, string $metodo_fallback, float $total_esperado): array
    {
        $total_esperado = round($total_esperado, 2);
        if ($total_esperado <= 0) {
            throw new Exception("El total de la venta debe ser mayor a cero.");
        }

        $pagos_limpios = [];
        if (!empty($pagos_raw)) {
            foreach ($pagos_raw as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $metodo = trim((string)($p['metodo'] ?? $p['metodo_pago'] ?? ''));
                $monto_raw = isset($p['monto']) ? str_replace(',', '.', (string)$p['monto']) : '';
                $monto = is_numeric($monto_raw) ? round((float)$monto_raw, 2) : 0.0;
                $pagos_limpios[] = [
                    'metodo' => $metodo,
                    'monto'  => $monto
                ];
            }
        } elseif ($metodo_fallback !== '') {
            $pagos_limpios[] = [
                'metodo' => trim($metodo_fallback),
                'monto'  => $total_esperado
            ];
        }

        if (empty($pagos_limpios)) {
            throw new Exception("Debe especificar al menos un medio de pago.");
        }

        if (count($pagos_limpios) > 2) {
            throw new Exception("Solo se permiten hasta 2 medios de pago combinados por venta.");
        }

        // Si es pago único y no se especificó monto explícito, asume el total de la venta
        if (count($pagos_limpios) === 1 && $pagos_limpios[0]['monto'] <= 0) {
            $pagos_limpios[0]['monto'] = $total_esperado;
        }

        $metodos_vistos = [];
        $suma_pagos = 0.0;
        $monto_efectivo = 0.0;

        foreach ($pagos_limpios as $idx => $pago) {
            $num = $idx + 1;
            if (!in_array($pago['metodo'], self::METODOS_PAGO, true)) {
                throw new Exception("El medio de pago #$num ('{$pago['metodo']}') no es válido.");
            }
            if ($pago['monto'] <= 0) {
                throw new Exception("El monto del medio de pago '{$pago['metodo']}' debe ser mayor a cero.");
            }
            if (in_array($pago['metodo'], $metodos_vistos, true)) {
                throw new Exception("No puede seleccionar dos veces el mismo medio de pago ('{$pago['metodo']}') en un pago combinado.");
            }
            $metodos_vistos[] = $pago['metodo'];
            $suma_pagos = round($suma_pagos + $pago['monto'], 2);

            if ($pago['metodo'] === 'Efectivo') {
                $monto_efectivo = round($monto_efectivo + $pago['monto'], 2);
            }
        }

        if (abs($suma_pagos - $total_esperado) > 0.01) {
            throw new Exception(
                "La suma de los pagos ($" . number_format($suma_pagos, 2, '.', '') .
                ") debe ser exactamente igual al total de la venta ($" . number_format($total_esperado, 2, '.', '') . ")."
            );
        }

        // Ajuste de centavo por redondeo flotante si está dentro de 0.01
        $diff = round($total_esperado - $suma_pagos, 2);
        if ($diff !== 0.0) {
            $lastIdx = count($pagos_limpios) - 1;
            $pagos_limpios[$lastIdx]['monto'] = round($pagos_limpios[$lastIdx]['monto'] + $diff, 2);
            if ($pagos_limpios[$lastIdx]['metodo'] === 'Efectivo') {
                $monto_efectivo = round($monto_efectivo + $diff, 2);
            }
        }

        $es_combinado = count($pagos_limpios) === 2;
        $metodo_resumen = $es_combinado
            ? ($pagos_limpios[0]['metodo'] . ' + ' . $pagos_limpios[1]['metodo'])
            : $pagos_limpios[0]['metodo'];

        return [
            'pagos'          => $pagos_limpios,
            'metodo_resumen' => $metodo_resumen,
            'monto_efectivo' => $monto_efectivo,
            'es_combinado'   => $es_combinado
        ];
    }

    /**
     * Obtiene los pagos individuales asociados a una venta.
     */
    public function get_sale_payments(int $id_venta): array
    {
        $stmt = $this->conn->prepare("SELECT id_pago, metodo_pago, monto FROM venta_pago WHERE id_venta = ? ORDER BY id_pago ASC");
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $res = $stmt->get_result();
        $pagos = [];
        while ($row = $res->fetch_assoc()) {
            $pagos[] = [
                'id_pago'     => (int)$row['id_pago'],
                'metodo_pago' => $row['metodo_pago'],
                'monto'       => (float)$row['monto']
            ];
        }
        $stmt->close();

        if (empty($pagos)) {
            $stmtV = $this->conn->prepare("SELECT metodo_de_pago, total FROM venta WHERE id_venta = ? LIMIT 1");
            $stmtV->bind_param("i", $id_venta);
            $stmtV->execute();
            $v = $stmtV->get_result()->fetch_assoc();
            $stmtV->close();
            if ($v) {
                $pagos[] = [
                    'id_pago'     => 0,
                    'metodo_pago' => trim((string)($v['metodo_de_pago'] ?? '')) ?: 'Efectivo',
                    'monto'       => (float)($v['total'] ?? 0)
                ];
            }
        }

        return $pagos;
    }

    public function get_sales($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql = "SELECT 
                    v.id_venta, 
                    v.fecha, 
                    v.total, 
                    COALESCE(
                        (SELECT CASE 
                            WHEN COUNT(*) > 1 THEN GROUP_CONCAT(CONCAT(vp.metodo_pago, ' ($', FORMAT(vp.monto, 2), ')') ORDER BY vp.id_pago ASC SEPARATOR ' + ')
                            WHEN COUNT(*) = 1 THEN MAX(vp.metodo_pago)
                            ELSE NULL
                         END
                         FROM venta_pago vp WHERE vp.id_venta = v.id_venta),
                        NULLIF(TRIM(v.metodo_de_pago), ''), 
                        'N/A'
                    ) AS metodo_de_pago,
                    v.estado,
                    COALESCE(
                        NULLIF(GROUP_CONCAT(
                            CASE WHEN dv.id_detalle_venta IS NOT NULL THEN
                                CONCAT(dv.cantidad, 'x ', COALESCE(NULLIF(TRIM(dv.nombre_producto), ''), p.nombre, 'Producto'))
                            ELSE NULL END
                            ORDER BY dv.id_detalle_venta ASC SEPARATOR ' · '
                        ), ''),
                        NULLIF(TRIM(v.descripcion), ''),
                        'Venta directa'
                    ) AS producto,
                    COALESCE(SUM(dv.cantidad), 1) AS cantidad
                FROM venta v
                LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                LEFT JOIN producto p ON dv.id_producto = p.id_producto
                GROUP BY v.id_venta
                ORDER BY v.fecha DESC, v.id_venta DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        $stmt->close();
        return $sales;
    }

    public function get_sales_count()
    {
        $sql = "SELECT COUNT(*) as total FROM venta";
        $res = $this->conn->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    public function find_sale_by_id(int $id)
    {
        $stmt = $this->conn->prepare("SELECT v.id_venta, v.fecha, v.total, COALESCE(NULLIF(TRIM(v.metodo_de_pago), ''), 'N/A') AS metodo_de_pago, v.estado, v.descripcion FROM venta v WHERE v.id_venta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $pagos = $this->get_sale_payments($id);
            $row['pagos'] = $pagos;
            if (count($pagos) > 1) {
                $partes = array_map(fn($p) => $p['metodo_pago'] . ' ($' . number_format((float)$p['monto'], 2) . ')', $pagos);
                $row['metodo_de_pago_detalle'] = implode(' + ', $partes);
            } elseif (count($pagos) === 1) {
                $row['metodo_de_pago_detalle'] = $pagos[0]['metodo_pago'];
            } else {
                $row['metodo_de_pago_detalle'] = $row['metodo_de_pago'];
            }
        }

        if ($row && !empty($row['descripcion']) && preg_match('/Cobro de Reparaci[oó]n #([^\s-]+)/iu', $row['descripcion'], $matches)) {
            $codigo_orden = trim($matches[1]);
            $stmt_c = $this->conn->prepare("SELECT c.nombre AS cliente_nombre, c.telefono AS cliente_telefono FROM reparacion r LEFT JOIN cliente c ON r.id_cliente = c.id_cliente WHERE r.codigo_orden = ? LIMIT 1");
            if ($stmt_c) {
                $stmt_c->bind_param("s", $codigo_orden);
                $stmt_c->execute();
                $res_c = $stmt_c->get_result()->fetch_assoc();
                $stmt_c->close();
                if (!empty($res_c['cliente_nombre'])) {
                    $row['cliente_nombre'] = $res_c['cliente_nombre'];
                }
                if (!empty($res_c['cliente_telefono'])) {
                    $row['cliente_telefono'] = $res_c['cliente_telefono'];
                }
            }
        }

        return $row;
    }

    public function get_sale_details(int $id_venta)
    {
        $stmt = $this->conn->prepare("SELECT nombre_producto, cantidad, precio_unitario 
                                     FROM detalle_venta 
                                     WHERE id_venta = ?");
        $stmt->bind_param("i", $id_venta);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = [];
        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }
        $stmt->close();
        return $details;
    }

    public function get_active_products_in_stock()
    {
        $sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, p.precio, i.cantidad
                FROM producto p
                JOIN inventario i ON p.id_producto = i.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE i.cantidad > 0 AND p.is_active = 1 AND " . ServiceProduct::excludeSql('p') . "
                ORDER BY p.nombre ASC";
        $result = $this->conn->query($sql);
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }

    public function get_sales_daily_totals()
    {
        $sql = "SELECT DATE(fecha) as sale_date, SUM(total) as daily_total 
                FROM venta 
                WHERE estado != 'anulada'
                GROUP BY DATE(fecha) 
                ORDER BY sale_date DESC 
                LIMIT 30";
        $result = $this->conn->query($sql);
        $dates = [];
        $totals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $dates[] = $row['sale_date'];
                $totals[] = (float)$row['daily_total'];
            }
        }
        return ['dates' => array_reverse($dates), 'totals' => array_reverse($totals)];
    }

    public function get_sales_by_payment_methods()
    {
        $sql = "SELECT 
                    COALESCE(NULLIF(TRIM(vp.metodo_pago), ''), NULLIF(TRIM(v.metodo_de_pago), ''), 'Otro') AS metodo_de_pago,
                    SUM(COALESCE(vp.monto, v.total)) AS amount
                FROM venta v
                LEFT JOIN venta_pago vp ON v.id_venta = vp.id_venta
                WHERE v.estado != 'anulada'
                GROUP BY COALESCE(NULLIF(TRIM(vp.metodo_pago), ''), NULLIF(TRIM(v.metodo_de_pago), ''), 'Otro')";
        $result = $this->conn->query($sql);
        $methods = [];
        $amounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $methods[] = empty($row['metodo_de_pago']) ? 'Otro' : $row['metodo_de_pago'];
                $amounts[] = (float)$row['amount'];
            }
        }
        return ['methods' => $methods, 'amounts' => $amounts];
    }

    public function get_top_products($limit = 5)
    {
        $limit = (int) $limit;
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(pd.marca, ''), ' ', COALESCE(pd.modelo, ''))), ''), p.nombre) as nombre_real,
                       SUM(dv.cantidad) as total_vendido 
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                JOIN producto p ON dv.id_producto = p.id_producto
                JOIN producto_detalle pd ON dv.id_producto = pd.id_producto
                WHERE v.estado = 'completada' AND $excludeServices
                GROUP BY dv.id_producto
                ORDER BY total_vendido DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $names = [];
        $quantities = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $names[] = $row['nombre_real'];
                $quantities[] = (int)$row['total_vendido'];
            }
        }
        $stmt->close();
        return ['names' => $names, 'quantities' => $quantities];
    }

    public function get_revenue_by_category()
    {
        $sql = "SELECT COALESCE(pd.categoria, 'Otros') as categoria, SUM(dv.cantidad * dv.precio_unitario) as total_ingresos
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                JOIN producto_detalle pd ON dv.id_producto = pd.id_producto
                WHERE v.estado = 'completada'
                GROUP BY pd.categoria
                ORDER BY total_ingresos DESC";
        $result = $this->conn->query($sql);
        $labels = [];
        $values = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['categoria'];
                $values[] = (float)$row['total_ingresos'];
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function get_top_profitable_products($limit = 5)
    {
        $limit = (int) $limit;
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(pd.marca, ''), ' ', COALESCE(pd.modelo, ''))), ''), p.nombre) as nombre_real, 
                        SUM((dv.precio_unitario - COALESCE((SELECT AVG(dc.precio_compra) FROM detalle_compra dc JOIN compra c ON dc.id_compra = c.id_compra WHERE dc.id_producto = p.id_producto AND c.estado = 'Recibido'), 0)) * dv.cantidad) as rentabilidad
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                JOIN producto p ON dv.id_producto = p.id_producto
                JOIN producto_detalle pd ON p.id_producto = pd.id_producto
                WHERE v.estado = 'completada' AND $excludeServices
                GROUP BY dv.id_producto
                ORDER BY rentabilidad DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $names = [];
        $values = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $names[] = $row['nombre_real'];
                $values[] = (float)$row['rentabilidad'];
            }
        }
        $stmt->close();
        return ['names' => $names, 'values' => $values];
    }

    public function get_sales_by_category()
    {
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT COALESCE(pd.categoria, 'Otros') as categoria, SUM(dv.cantidad) as total_unidades
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                JOIN producto p ON dv.id_producto = p.id_producto
                JOIN producto_detalle pd ON dv.id_producto = pd.id_producto
                WHERE v.estado = 'completada' AND $excludeServices
                GROUP BY pd.categoria
                ORDER BY total_unidades DESC";
        $result = $this->conn->query($sql);
        $labels = [];
        $values = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['categoria'];
                $values[] = (int)$row['total_unidades'];
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function get_sales_export_data()
    {
        $sql = "SELECT 
                    v.id_venta, 
                    v.fecha, 
                    v.total, 
                    COALESCE(
                        (SELECT CASE 
                            WHEN COUNT(*) > 1 THEN GROUP_CONCAT(CONCAT(vp.metodo_pago, ' ($', FORMAT(vp.monto, 2), ')') ORDER BY vp.id_pago ASC SEPARATOR ' + ')
                            WHEN COUNT(*) = 1 THEN MAX(vp.metodo_pago)
                            ELSE NULL
                         END
                         FROM venta_pago vp WHERE vp.id_venta = v.id_venta),
                        NULLIF(TRIM(v.metodo_de_pago), ''), 
                        'N/A'
                    ) AS metodo_de_pago,
                    v.estado,
                    COALESCE(
                        NULLIF(GROUP_CONCAT(
                            CASE WHEN dv.id_detalle_venta IS NOT NULL THEN
                                CONCAT(dv.cantidad, 'x ', COALESCE(NULLIF(TRIM(dv.nombre_producto), ''), p.nombre, 'Producto'))
                            ELSE NULL END
                            ORDER BY dv.id_detalle_venta ASC SEPARATOR ' · '
                        ), ''),
                        NULLIF(TRIM(v.descripcion), ''),
                        'Venta directa'
                    ) AS producto,
                    COALESCE(SUM(dv.cantidad), 1) AS cantidad
                FROM venta v
                LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                LEFT JOIN producto p ON dv.id_producto = p.id_producto
                GROUP BY v.id_venta
                ORDER BY v.fecha DESC, v.id_venta DESC";
        $result = $this->conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function create_sale_transaction(array $items, string|array $metodo_o_pagos, int|string $user_id, string $descripcion = '')
    {
        require_once __DIR__ . '/../../../config/audit.php';
        
        $this->conn->begin_transaction();
        try {
            $total_venta = 0;
            $fecha = date('Y-m-d H:i:s');

            // Fetch username for explicitly stating it in the log
            $username_audit = "Desconocido";
            if ($user_id !== 'guest' && $user_id > 0) {
                $stmt = $this->conn->prepare("SELECT nombre_usuario FROM usuario WHERE id_usuario = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res_u = $stmt->get_result();
                if ($res_u->num_rows > 0) {
                    $username_audit = $res_u->fetch_assoc()['nombre_usuario'];
                }
                $stmt->close();
            } elseif ($user_id === 'guest') {
                $username_audit = "Invitado";
                $user_id = NULL; 
            }

            $grouped_items = [];
            foreach ($items as $item) {
                $id = (int)$item['id'];
                $cant = (int)$item['cantidad'];
                
                if ($cant <= 0) {
                    throw new Exception("La cantidad debe ser mayor a cero.");
                }
                
                if (isset($grouped_items[$id])) {
                    $grouped_items[$id]['cantidad'] += $cant;
                } else {
                    $grouped_items[$id] = $item;
                    $grouped_items[$id]['cantidad'] = $cant;
                }
            }
            $items = array_values($grouped_items);

            // El producto contable de servicio técnico solo se factura desde el taller
            $service_id = ServiceProduct::findId($this->conn);
            foreach ($items as $item) {
                if ($service_id !== null && (int)$item['id'] === $service_id) {
                    throw new Exception("El servicio técnico del taller no puede venderse manualmente.");
                }
            }

            foreach ($items as $item) {
                $stmt = $this->conn->prepare(
                    "SELECT p.precio, i.cantidad, p.is_active FROM inventario i
                     JOIN producto p ON i.id_producto = p.id_producto
                     WHERE p.id_producto = ? FOR UPDATE"
                );
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows === 0) throw new Exception("Producto ID " . $item['id'] . " no encontrado");
                $prod = $res->fetch_assoc();
                $stmt->close();

                if ((int)$prod['is_active'] === 0) {
                    throw new Exception("El producto ID " . $item['id'] . " no está activo para la venta.");
                }

                if ($prod['cantidad'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto ID " . $item['id']);
                }
                $total_venta += $prod['precio'] * $item['cantidad'];
            }

            $total_venta = round((float)$total_venta, 2);

            // Validar y normalizar el pago (único o combinado de hasta 2 medios) contra el total exacto de la venta
            $pagos_raw = is_array($metodo_o_pagos) ? $metodo_o_pagos : [];
            $metodo_fallback = is_string($metodo_o_pagos) ? $metodo_o_pagos : '';
            $pago_info = self::validate_and_normalize_payments($pagos_raw, $metodo_fallback, $total_venta);
            $metodo_pago_resumen = $pago_info['metodo_resumen'];
            $pagos_validados = $pago_info['pagos'];
            $monto_efectivo = $pago_info['monto_efectivo'];
            $es_combinado = $pago_info['es_combinado'];

            $stmt = $this->conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago, id_usuario, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsis", $fecha, $total_venta, $metodo_pago_resumen, $user_id, $descripcion);
            if (!$stmt->execute()) throw new Exception("Error al crear venta");
            $id_venta = $this->conn->insert_id;
            $stmt->close();

            // Guardar cada pago por separado (medio y monto) asociado a la venta
            $stmtPago = $this->conn->prepare("INSERT INTO venta_pago (id_venta, metodo_pago, monto) VALUES (?, ?, ?)");
            foreach ($pagos_validados as $pItem) {
                $mNombre = $pItem['metodo'];
                $mMonto = (float)$pItem['monto'];
                $stmtPago->bind_param("isd", $id_venta, $mNombre, $mMonto);
                if (!$stmtPago->execute()) {
                    throw new Exception("Error al registrar el desglose de pago de la venta.");
                }
            }
            $stmtPago->close();

            $nombres_vendidos = [];
            foreach ($items as $item) {
                $stmt = $this->conn->prepare(
                    "SELECT p.precio, p.nombre, d.marca, d.modelo
                     FROM producto p
                     JOIN producto_detalle d ON p.id_producto = d.id_producto
                     WHERE p.id_producto = ?"
                );
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $prod = $stmt->get_result()->fetch_assoc();
                $precio_unitario = $prod['precio'];
                $stmt->close();

                $nombre_producto = $prod['nombre'] . ' ' . $prod['marca'] . ' ' . $prod['modelo'];
                $nombres_vendidos[] = $item['cantidad'] . "x " . $nombre_producto;

                $stmt = $this->conn->prepare(
                    "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("iiids", $id_venta, $item['id'], $item['cantidad'], $precio_unitario, $nombre_producto);
                if (!$stmt->execute()) throw new Exception("Error al crear detalle");
                $stmt->close();

                $stmt = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id_producto = ?");
                $stmt->bind_param("ii", $item['cantidad'], $item['id']);
                if (!$stmt->execute()) throw new Exception("Error al actualizar stock");
                $stmt->close();

                // Registrar en la auditoría el descuento de stock para el módulo de Inventario
                audit_log($this->conn, 'INVENTORY_UPDATE', $user_id, 'Inventario', (int)$item['id'],
                    "Stock de '$nombre_producto' reducido en {$item['cantidad']} unidades por venta (Usuario: $username_audit)");
            }

            $productos_str = implode(", ", $nombres_vendidos);

            // Libro de Caja inmutable dentro de la misma transacción:
            // - Bloquea la venta si hay efectivo ($monto_efectivo > 0) y no existe caja abierta.
            // - Registra los pagos con su `medio_pago` e `id_venta` (el arqueo físico filtra medio_pago = 'efectivo').
            $uid_int = is_numeric($user_id) && (int)$user_id > 0 ? (int)$user_id : null;
            CajaModel::recordSaleMovementsInTransaction(
                $this->conn,
                (int)$id_venta,
                null,
                $pagos_validados,
                $monto_efectivo,
                $total_venta,
                $es_combinado,
                'Venta',
                $productos_str,
                $uid_int
            );

            $this->conn->commit();

            $pagos_log_parts = array_map(fn($p) => "{$p['metodo']}: \$" . number_format((float)$p['monto'], 2, '.', ''), $pagos_validados);
            $pagos_log_str = implode(" + ", $pagos_log_parts);

            audit_log($this->conn, 'SALE_CREATE', $user_id, 'venta', $id_venta,
                "Usuario $username_audit registró venta. Total: \$$total_venta. Pagos: $pagos_log_str. Productos: $productos_str");

            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function rollback_sale(int $id_venta, int|string $user_id)
    {
        $this->conn->begin_transaction();
        try {
            // Verificar estado actual
            $stmt = $this->conn->prepare("SELECT estado, descripcion, total, metodo_de_pago FROM venta WHERE id_venta = ? FOR UPDATE");
            $stmt->bind_param("i", $id_venta);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) throw new Exception("Venta no encontrada");
            $venta = $res->fetch_assoc();
            $stmt->close();

            if ($venta['estado'] === 'anulada') {
                throw new Exception("La venta ya se encuentra anulada");
            }

            // Actualizar estado
            $stmt = $this->conn->prepare("UPDATE venta SET estado = 'anulada' WHERE id_venta = ?");
            $stmt->bind_param("i", $id_venta);
            if (!$stmt->execute()) throw new Exception("Error al actualizar el estado de la venta");
            $stmt->close();

            // Fetch username
            $username_audit = "Desconocido";
            if ($user_id !== 'guest' && $user_id > 0) {
                $stmt = $this->conn->prepare("SELECT nombre_usuario FROM usuario WHERE id_usuario = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res_u = $stmt->get_result();
                if ($res_u->num_rows > 0) {
                    $username_audit = $res_u->fetch_assoc()['nombre_usuario'];
                }
                $stmt->close();
            } elseif ($user_id === 'guest') {
                $username_audit = "Invitado";
            }
            $audit_user = ($user_id === 'guest') ? null : $user_id;
            $service_id = ServiceProduct::findId($this->conn);

            // Devolver stock
            $stmt = $this->conn->prepare("SELECT d.id_producto, d.cantidad, d.nombre_producto FROM detalle_venta d WHERE d.id_venta = ?");
            $stmt->bind_param("i", $id_venta);
            $stmt->execute();
            $detalles = $stmt->get_result();
            $items_revertidos = 0;
            
            require_once __DIR__ . '/../../../config/audit.php';
            
            while ($row = $detalles->fetch_assoc()) {
                // El servicio técnico no es stock físico: no se devuelve al inventario
                if ($service_id !== null && (int)$row['id_producto'] === $service_id) {
                    continue;
                }

                $stmt_upd = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad + ? WHERE id_producto = ?");
                $stmt_upd->bind_param("ii", $row['cantidad'], $row['id_producto']);
                if (!$stmt_upd->execute()) throw new Exception("Error al devolver el stock al inventario");
                $stmt_upd->close();
                
                // Registrar en auditoría el retorno de stock
                audit_log($this->conn, 'INVENTORY_UPDATE', $audit_user, 'Inventario', (int)$row['id_producto'],
                    "Stock de '{$row['nombre_producto']}' devuelto ({$row['cantidad']} unidades) por anulación de venta");
                
                $items_revertidos++;
            }
            $stmt->close();

            // Revertir en Caja ÚNICAMENTE la parte que fue abonada en Efectivo
            $stmtPagos = $this->conn->prepare("SELECT metodo_pago, monto FROM venta_pago WHERE id_venta = ?");
            $stmtPagos->bind_param("i", $id_venta);
            $stmtPagos->execute();
            $resPagos = $stmtPagos->get_result();
            $monto_efectivo_revertir = 0.0;
            $tiene_pagos_tabla = false;
            while ($pRow = $resPagos->fetch_assoc()) {
                $tiene_pagos_tabla = true;
                if (mb_strtolower(trim((string)$pRow['metodo_pago']), 'UTF-8') === 'efectivo') {
                    $monto_efectivo_revertir += (float)$pRow['monto'];
                }
            }
            $stmtPagos->close();

            if (!$tiene_pagos_tabla && mb_strtolower(trim((string)($venta['metodo_de_pago'] ?? '')), 'UTF-8') === 'efectivo') {
                $monto_efectivo_revertir = (float)($venta['total'] ?? 0);
            }

            // Registrar contra-asiento inmutable en Caja dentro de la misma transacción (vinculado por id_venta)
            $uid_int = is_numeric($audit_user) && (int)$audit_user > 0 ? (int)$audit_user : null;
            CajaModel::reverseSaleMovementsInTransaction($this->conn, $id_venta, $monto_efectivo_revertir, $uid_int);

            // Si la venta corresponde al cobro de una reparación, registrar nota en historial sin devolver a la cola de cobro
            $reparacion_afectada = null;
            $prefijo = 'Cobro de Reparación #';
            $desc_venta = (string)($venta['descripcion'] ?? '');
            if (str_starts_with($desc_venta, $prefijo)) {
                $resto = substr($desc_venta, strlen($prefijo));
                $codigo_orden = trim(explode(' - ', $resto, 2)[0]);

                $stmt_r = $this->conn->prepare("SELECT id_reparacion, estado FROM reparacion WHERE codigo_orden = ? FOR UPDATE");
                $stmt_r->bind_param("s", $codigo_orden);
                $stmt_r->execute();
                $rep = $stmt_r->get_result()->fetch_assoc();
                $stmt_r->close();

                if ($rep) {
                    $id_rep = (int)$rep['id_reparacion'];
                    $estado_actual = $rep['estado'];
                    $nota = "Cobro anulado (venta #$id_venta). Se emite Nota de Crédito. La reparación permanece finalizada.";
                    $hist_user = is_numeric($audit_user) && (int)$audit_user > 0 ? (int)$audit_user : null;
                    $stmt_h = $this->conn->prepare("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) VALUES (?, ?, ?, ?, ?)");
                    $stmt_h->bind_param("isssi", $id_rep, $estado_actual, $estado_actual, $nota, $hist_user);
                    $stmt_h->execute();
                    $stmt_h->close();

                    $reparacion_afectada = $codigo_orden;
                }
            }

            $this->conn->commit();

            $extra = $reparacion_afectada ? " Reparación #$reparacion_afectada con nota de cobro anulado." : '';
            audit_log($this->conn, 'SALE_ROLLBACK', $audit_user, 'venta', $id_venta,
                "Venta anulada por usuario $username_audit. Stock devuelto para $items_revertidos producto(s).$extra");

            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function get_sales_daily($days = 7)
    {
        $days = intval($days);
        $sql = "SELECT DATE(fecha) as dia, SUM(total) as total_ventas 
                FROM venta 
                WHERE estado != 'anulada' AND fecha >= DATE(NOW()) - INTERVAL ? DAY 
                GROUP BY DATE(fecha) 
                ORDER BY dia ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['dia'];
                $data[] = $row['total_ventas'];
            }
        }
        $stmt->close();
        return ['labels' => $labels, 'data' => $data];
    }

    public function get_sales_methods()
    {
        $sql = "SELECT 
                    COALESCE(NULLIF(TRIM(vp.metodo_pago), ''), NULLIF(TRIM(v.metodo_de_pago), ''), 'Otro') AS metodo,
                    SUM(COALESCE(vp.monto, v.total)) AS total_ventas
                FROM venta v
                LEFT JOIN venta_pago vp ON v.id_venta = vp.id_venta
                WHERE v.estado != 'anulada' AND v.fecha >= DATE(NOW()) - INTERVAL 30 DAY
                GROUP BY COALESCE(NULLIF(TRIM(vp.metodo_pago), ''), NULLIF(TRIM(v.metodo_de_pago), ''), 'Otro')";
        $result = $this->conn->query($sql);
        
        $labels = [];
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['metodo'];
                $data[] = (float)$row['total_ventas'];
            }
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function get_top_sales_products($limit = 5)
    {
        $limit = intval($limit);
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $serviceName = ServiceProduct::NAME;
        $sql = "SELECT COALESCE(dv.nombre_producto, p.nombre) as nombre, SUM(dv.cantidad) as total_vendido
                FROM detalle_venta dv
                LEFT JOIN producto p ON dv.id_producto = p.id_producto
                LEFT JOIN producto_detalle pd ON p.id_producto = pd.id_producto
                JOIN venta v ON dv.id_venta = v.id_venta
                WHERE v.estado != 'anulada'
                  AND v.fecha >= DATE(NOW()) - INTERVAL 30 DAY
                  AND COALESCE(dv.nombre_producto, '') <> '$serviceName'
                  AND (p.id_producto IS NULL OR $excludeServices)
                GROUP BY dv.id_producto, COALESCE(dv.nombre_producto, p.nombre)
                ORDER BY total_vendido DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['nombre'];
                $data[] = (int)$row['total_vendido'];
            }
        }
        $stmt->close();
        return ['labels' => $labels, 'data' => $data];
    }
}

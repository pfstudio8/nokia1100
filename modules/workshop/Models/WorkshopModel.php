<?php
// modules/workshop/Models/WorkshopModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';
require_once __DIR__ . '/../../../classes/ServiceProduct.php';

class WorkshopModel extends BaseModel
{
    public function get_repairs($status = '', $search = '', $limit = 50, $offset = 0)
    {
        $where_parts = [];
        $types = "";
        $params = [];

        if ($status) {
            $where_parts[] = "r.estado = ?";
            $types .= "s";
            $params[] = $status;
        }
        if ($search) {
            $wildcard = "%" . $search . "%";
            $where_parts[] = "(r.codigo_orden LIKE ? OR c.nombre LIKE ? OR r.equipo_marca LIKE ? OR r.equipo_modelo LIKE ?)";
            $types .= "ssss";
            array_push($params, $wildcard, $wildcard, $wildcard, $wildcard);
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT r.id_reparacion, r.codigo_orden, c.nombre AS cliente_nombre, r.equipo_marca, r.equipo_modelo, r.estado, r.fecha_ingreso, r.presupuesto, r.costo_total 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                $where_clause 
                ORDER BY r.fecha_ingreso DESC
                LIMIT ? OFFSET ?";

        $types .= "ii";
        array_push($params, (int)$limit, (int)$offset);

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $repairs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $repairs[] = $row;
            }
        }
        $stmt->close();
        return $repairs;
    }

    public function get_repairs_count($status = '', $search = '')
    {
        $where_parts = [];
        $types = "";
        $params = [];

        if ($status) {
            $where_parts[] = "r.estado = ?";
            $types .= "s";
            $params[] = $status;
        }
        if ($search) {
            $wildcard = "%" . $search . "%";
            $where_parts[] = "(r.codigo_orden LIKE ? OR c.nombre LIKE ? OR r.equipo_marca LIKE ? OR r.equipo_modelo LIKE ?)";
            $types .= "ssss";
            array_push($params, $wildcard, $wildcard, $wildcard, $wildcard);
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT COUNT(*) as total
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                $where_clause";

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $total = (int)$row['total'];
        }
        $stmt->close();
        return $total;
    }

    public function get_chargeable_repairs()
    {
        $sql = "SELECT r.id_reparacion, r.codigo_orden, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono, c.email AS cliente_email,
                       r.equipo_marca, r.equipo_modelo, r.equipo_imei, r.estado, r.fecha_ingreso, r.presupuesto, r.costo_total, r.falla_declarada, r.observaciones,
                       (SELECT rh.nota FROM reparacion_historial rh WHERE rh.id_reparacion = r.id_reparacion AND (rh.nota LIKE 'Finalizado por técnico%' OR rh.nota LIKE '%Listo%' OR rh.estado_nuevo = 'Listo') AND rh.nota != '' AND rh.nota NOT LIKE 'Cobrado%' ORDER BY rh.id_historial DESC LIMIT 1) AS resolucion_tecnica,
                       (SELECT u.nombre_usuario FROM reparacion_historial rh JOIN usuario u ON rh.id_usuario = u.id_usuario WHERE rh.id_reparacion = r.id_reparacion AND (rh.nota LIKE 'Finalizado por técnico%' OR rh.estado_nuevo = 'Listo') ORDER BY rh.id_historial DESC LIMIT 1) AS tecnico_nombre
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                WHERE r.estado IN ('Listo', 'Listo para Retirar')
                ORDER BY r.fecha_ingreso ASC";
        $result = $this->conn->query($sql);
        $repairs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $repairs[] = $row;
            }
        }
        return $repairs;
    }

    public function get_ready_to_charge_count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM reparacion WHERE estado IN ('Listo', 'Listo para Retirar')";
        $res = $this->conn->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    public function send_repair_to_cashier(int $id_reparacion, float $presupuesto, string $nota, int $id_usuario): bool
    {
        $id_reparacion = intval($id_reparacion);

        // Consultar el estado y presupuesto actual de la reparación
        $stmt_old = $this->conn->prepare("SELECT estado, presupuesto FROM reparacion WHERE id_reparacion = ?");
        $stmt_old->bind_param("i", $id_reparacion);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        $row_old = ($res && $row = $res->fetch_assoc()) ? $row : null;
        $stmt_old->close();

        if (!$row_old) {
            throw new Exception("Orden de reparación no encontrada.");
        }

        // Validación estricta: Solo se puede mandar a ventas si está en estado Listo
        if ($row_old['estado'] !== 'Listo') {
            throw new Exception("Solo se pueden enviar a Ventas las órdenes que se encuentren en estado 'Listo'.");
        }

        $old_state = $row_old['estado'];
        $current_db_budget = floatval($row_old['presupuesto'] ?? 0);

        // Mantener el presupuesto puesto previamente si el valor enviado viene en 0 o vacío
        $budget_val = round(floatval($presupuesto), 2);
        if ($budget_val <= 0 && $current_db_budget > 0) {
            $budget_val = $current_db_budget;
        }

        $nuevo_estado = 'Listo';
        $stmt = $this->conn->prepare("UPDATE reparacion SET estado = ?, presupuesto = ? WHERE id_reparacion = ?");
        $stmt->bind_param("sdi", $nuevo_estado, $budget_val, $id_reparacion);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $nota_hist = !empty(trim($nota)) 
                ? "Finalizado por técnico y enviado a Caja: " . trim($nota) 
                : "Finalizado por técnico y enviado a Caja para cobro y entrega al cliente";
            $stmt_hist = $this->conn->prepare("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt_hist->bind_param("isssi", $id_reparacion, $old_state, $nuevo_estado, $nota_hist, $id_usuario);
            $stmt_hist->execute();
            $stmt_hist->close();
        }
        return $ok;
    }

    public function get_delivery_receipt_data(int $id_reparacion): ?array
    {
        $repair = $this->find_repair_by_id($id_reparacion);
        if (!$repair) return null;

        $repuestos = $this->get_repair_repuestos($id_reparacion);
        $historial = $this->get_repair_historial($id_reparacion);

        $codigo_busqueda = $repair['codigo_orden'];
        $stmt_v = $this->conn->prepare("SELECT v.id_venta, v.fecha, v.total, v.metodo_de_pago, u.nombre_usuario AS cajero 
                                        FROM venta v 
                                        LEFT JOIN usuario u ON v.id_usuario = u.id_usuario 
                                        WHERE v.descripcion LIKE CONCAT('Cobro de Reparación #', ?, '%') 
                                        ORDER BY v.id_venta DESC LIMIT 1");
        $stmt_v->bind_param("s", $codigo_busqueda);
        $stmt_v->execute();
        $res_v = $stmt_v->get_result();
        $venta = ($res_v && $res_v->num_rows > 0) ? $res_v->fetch_assoc() : null;
        $stmt_v->close();

        return [
            'repair' => $repair,
            'repuestos' => $repuestos,
            'historial' => $historial,
            'venta' => $venta
        ];
    }

    public function get_paid_repairs_history($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql = "SELECT v.id_venta, v.fecha, v.total, v.metodo_de_pago, v.descripcion, u.nombre_usuario 
                FROM venta v
                LEFT JOIN usuario u ON v.id_usuario = u.id_usuario
                WHERE v.descripcion LIKE 'Cobro de Reparación #%'
                ORDER BY v.fecha DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        $stmt->close();
        return $history;
    }

    public function get_paid_repairs_history_count()
    {
        $sql = "SELECT COUNT(*) as total
                FROM venta v
                WHERE v.descripcion LIKE 'Cobro de Reparación #%'";
        $result = $this->conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    public function find_repair_by_id(int $id)
    {
        $id = intval($id);
        $sql = "SELECT r.*, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono, c.email AS cliente_email 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                WHERE r.id_reparacion = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function find_repair_by_code(string $code)
    {
        $sql = "SELECT codigo_orden, equipo_marca, equipo_modelo, estado FROM reparacion WHERE codigo_orden = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $data;
    }

    public function get_repair_repuestos(int $id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT rr.*, p.nombre 
                FROM reparacion_repuesto rr 
                JOIN producto p ON rr.id_producto = p.id_producto 
                WHERE rr.id_reparacion = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_reparacion);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $repuestos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $repuestos[] = $row;
            }
        }
        $stmt->close();
        return $repuestos;
    }

    public function get_repair_historial(int $id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT h.*, u.nombre_usuario 
                FROM reparacion_historial h 
                LEFT JOIN usuario u ON h.id_usuario = u.id_usuario 
                WHERE id_reparacion = ? 
                ORDER BY fecha_cambio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_reparacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $historial = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $historial[] = $row;
            }
        }
        $stmt->close();
        return $historial;
    }

    public function get_active_products_in_stock()
    {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, i.cantidad 
                FROM producto p 
                JOIN inventario i ON p.id_producto = i.id_producto 
                WHERE p.is_active = 1 AND i.cantidad > 0 AND " . ServiceProduct::excludeSql('p') . "
                ORDER BY p.nombre ASC";
        $result = $this->conn->query($sql);
        $products = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }

    public function get_all_clients()
    {
        $sql = "SELECT id_cliente, nombre, telefono, email FROM cliente ORDER BY nombre ASC";
        $result = $this->conn->query($sql);
        $clients = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clients[] = $row;
            }
        }
        return $clients;
    }

    public function create_repair_order(int $id_cliente_existente, string $client_name, string $client_phone, string $client_email, string $brand, string $model, string $imei, string $failure, string $notes, float $budget, int $user_id)
    {
        $id_cliente_existente = intval($id_cliente_existente);
        $user_id = intval($user_id);
        $codigo_orden = date('ym') . mt_rand(1000, 9999);

        if ($id_cliente_existente > 0) {
            $id_cliente = $id_cliente_existente;
        } else {
            // Buscar cliente existente o crear uno nuevo con sentencia preparada
            $stmt = $this->conn->prepare("SELECT id_cliente FROM cliente WHERE nombre = ? AND telefono = ? LIMIT 1");
            $stmt->bind_param("ss", $client_name, $client_phone);
            $stmt->execute();
            $res_client = $stmt->get_result();
            if ($res_client && $row = $res_client->fetch_assoc()) {
                $id_cliente = (int)$row['id_cliente'];
            } else {
                $stmt_insert = $this->conn->prepare("INSERT INTO cliente (nombre, telefono, email) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $client_name, $client_phone, $client_email);
                if (!$stmt_insert->execute()) {
                    throw new Exception("Error al registrar cliente: " . $stmt_insert->error);
                }
                $id_cliente = (int)$this->conn->insert_id;
                $stmt_insert->close();
            }
            $stmt->close();
        }

        $stmt_rep = $this->conn->prepare("INSERT INTO reparacion (codigo_orden, id_cliente, equipo_marca, equipo_modelo, equipo_imei, falla_declarada, observaciones, presupuesto, id_usuario_recibe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_rep->bind_param("sisssssdi", $codigo_orden, $id_cliente, $brand, $model, $imei, $failure, $notes, $budget, $user_id);

        if (!$stmt_rep->execute()) {
            $err = $stmt_rep->error;
            $stmt_rep->close();
            throw new Exception("Error al crear la orden: " . $err);
        }
        $id_reparacion = (int)$this->conn->insert_id;
        $stmt_rep->close();

        // Historial inicial preparado
        $stmt_hist = $this->conn->prepare("INSERT INTO reparacion_historial (id_reparacion, estado_nuevo, nota, id_usuario) VALUES (?, 'Recibido', 'Ingreso inicial del equipo', ?)");
        $stmt_hist->bind_param("ii", $id_reparacion, $user_id);
        $stmt_hist->execute();
        $stmt_hist->close();

        return $id_reparacion;
    }

    public function update_repair_status_and_budget(int $id_reparacion, string $status, float $budget, string $note, int $user_id)
    {
        $id_reparacion = intval($id_reparacion);
        $user_id = intval($user_id);

        $stmt_old = $this->conn->prepare("SELECT estado, presupuesto FROM reparacion WHERE id_reparacion = ?");
        $stmt_old->bind_param("i", $id_reparacion);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        if (!$res_old || $res_old->num_rows === 0) {
            $stmt_old->close();
            throw new Exception("Orden de reparación no encontrada");
        }
        $row_old = $res_old->fetch_assoc();
        $old_state = $row_old['estado'];
        $prev_budget = floatval($row_old['presupuesto'] ?? 0);
        $stmt_old->close();

        // Preservar el presupuesto previo si el valor enviado viene en 0 o vacío
        $budget_val = round((float)$budget, 2);
        if ($budget_val <= 0 && $prev_budget > 0) {
            $budget_val = $prev_budget;
        }

        $sql_update = "UPDATE reparacion SET estado = ?, presupuesto = ?";
        if ($status === 'Entregado' && $old_state !== 'Entregado') {
            $sql_update .= ", fecha_entrega = CURRENT_TIMESTAMP";
        }
        $sql_update .= " WHERE id_reparacion = ?";

        $stmt_update = $this->conn->prepare($sql_update);
        $stmt_update->bind_param("sdi", $status, $budget_val, $id_reparacion);
        
        if ($stmt_update->execute()) {
            $stmt_update->close();
            if ($old_state !== $status || !empty($note)) {
                $stmt_hist = $this->conn->prepare("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) VALUES (?, ?, ?, ?, ?)");
                $stmt_hist->bind_param("isssi", $id_reparacion, $old_state, $status, $note, $user_id);
                $stmt_hist->execute();
                $stmt_hist->close();
            }
            return true;
        } else {
            $err = $stmt_update->error;
            $stmt_update->close();
            throw new Exception("Error al actualizar la orden: " . $err);
        }
    }

    public function add_repuesto_to_repair(int $id_reparacion, int $id_producto, int $quantity = 1)
    {
        $id_reparacion = intval($id_reparacion);
        $id_producto = intval($id_producto);
        $quantity = intval($quantity);

        if ($id_reparacion <= 0 || $id_producto <= 0 || $quantity <= 0) {
            throw new Exception("Parámetros de repuesto inválidos.");
        }

        $this->conn->begin_transaction();
        try {
            // 1. Bloquear y verificar el estado de la reparación (no debe estar 'Entregado')
            $stmt_rep = $this->conn->prepare("SELECT estado FROM reparacion WHERE id_reparacion = ? FOR UPDATE");
            $stmt_rep->bind_param("i", $id_reparacion);
            $stmt_rep->execute();
            $res_rep = $stmt_rep->get_result();
            $rep = $res_rep ? $res_rep->fetch_assoc() : null;
            $stmt_rep->close();

            if (!$rep) {
                throw new Exception("La reparación especificada no existe.");
            }
            if ($rep['estado'] === 'Entregado') {
                throw new Exception("No se pueden agregar repuestos a una reparación ya entregada.");
            }

            // 2. Bloquear y consultar stock en inventario de manera atómica
            $stmt_inv = $this->conn->prepare("SELECT cantidad FROM inventario WHERE id_producto = ? FOR UPDATE");
            $stmt_inv->bind_param("i", $id_producto);
            $stmt_inv->execute();
            $res_inv = $stmt_inv->get_result();
            $row_inv = $res_inv ? $res_inv->fetch_assoc() : null;
            $stmt_inv->close();

            if (!$row_inv || $row_inv['cantidad'] < $quantity) {
                throw new Exception("No hay stock suficiente de ese repuesto.");
            }

            // 3. Consultar precio unitario del producto
            $stmt_prod = $this->conn->prepare("SELECT precio FROM producto WHERE id_producto = ?");
            $stmt_prod->bind_param("i", $id_producto);
            $stmt_prod->execute();
            $res_prod = $stmt_prod->get_result();
            $prod = $res_prod ? $res_prod->fetch_assoc() : null;
            $stmt_prod->close();

            $precio = $prod ? (float)$prod['precio'] : 0.0;

            // 4. Insertar en reparacion_repuesto
            $stmt_add = $this->conn->prepare("INSERT INTO reparacion_repuesto (id_reparacion, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt_add->bind_param("iiid", $id_reparacion, $id_producto, $quantity, $precio);
            $stmt_add->execute();
            $stmt_add->close();

            // 5. Descontar stock
            $stmt_upd = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id_producto = ?");
            $stmt_upd->bind_param("ii", $quantity, $id_producto);
            $stmt_upd->execute();
            $stmt_upd->close();

            // 6. Actualizar costo total de la reparación
            $stmt_cost = $this->conn->prepare("UPDATE reparacion SET costo_total = costo_total + (? * ?) WHERE id_reparacion = ?");
            $stmt_cost->bind_param("dii", $precio, $quantity, $id_reparacion);
            $stmt_cost->execute();
            $stmt_cost->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function remove_repuesto_from_repair(int $id_reparacion_repuesto)
    {
        $id_reparacion_repuesto = intval($id_reparacion_repuesto);
        if ($id_reparacion_repuesto <= 0) {
            return false;
        }

        $this->conn->begin_transaction();
        try {
            // 1. Bloquear y verificar el repuesto junto al estado de la reparación padre
            $stmt = $this->conn->prepare("SELECT rr.id_reparacion, rr.id_producto, rr.cantidad, rr.precio_unitario, r.estado 
                                          FROM reparacion_repuesto rr 
                                          INNER JOIN reparacion r ON rr.id_reparacion = r.id_reparacion 
                                          WHERE rr.id_reparacion_repuesto = ? FOR UPDATE");
            $stmt->bind_param("i", $id_reparacion_repuesto);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                throw new Exception("El repuesto asignado no fue encontrado.");
            }

            if ($row['estado'] === 'Entregado') {
                throw new Exception("No se pueden eliminar repuestos de una orden ya entregada y cobrada.");
            }

            // 2. Eliminar la asignación de repuesto
            $stmt_del = $this->conn->prepare("DELETE FROM reparacion_repuesto WHERE id_reparacion_repuesto = ?");
            $stmt_del->bind_param("i", $id_reparacion_repuesto);
            $stmt_del->execute();
            $stmt_del->close();

            // 3. Devolver stock al inventario
            $stmt_inv = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad + ? WHERE id_producto = ?");
            $stmt_inv->bind_param("ii", $row['cantidad'], $row['id_producto']);
            $stmt_inv->execute();
            $stmt_inv->close();

            // 4. Restar costo total
            $costo = (float)$row['cantidad'] * (float)$row['precio_unitario'];
            $stmt_cost = $this->conn->prepare("UPDATE reparacion SET costo_total = costo_total - ? WHERE id_reparacion = ?");
            $stmt_cost->bind_param("di", $costo, $row['id_reparacion']);
            $stmt_cost->execute();
            $stmt_cost->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function charge_and_deliver_repair(int $id_reparacion, string $metodo_pago, int $id_usuario): int
    {
        $id_reparacion = intval($id_reparacion);
        $id_usuario = intval($id_usuario);

        $this->conn->begin_transaction();
        try {
            // 1. Bloquear y verificar orden para evitar condiciones de carrera o cobros duplicados
            $stmt = $this->conn->prepare("SELECT id_reparacion, codigo_orden, equipo_marca, equipo_modelo, estado, presupuesto, costo_total FROM reparacion WHERE id_reparacion = ? FOR UPDATE");
            $stmt->bind_param("i", $id_reparacion);
            $stmt->execute();
            $res = $stmt->get_result();
            $repair = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$repair) {
                throw new Exception("Reparación no encontrada.");
            }

            if (in_array($repair['estado'], ['Entregado', 'Cancelado'], true)) {
                throw new Exception("La reparación ya figura como '" . $repair['estado'] . "' y no puede volver a cobrarse.");
            }

            $presupuesto_base = floatval($repair['presupuesto']);
            $costo_repuestos = floatval($repair['costo_total'] ?? 0);
            $monto_total = $presupuesto_base + $costo_repuestos;
            $old_state = $repair['estado'];

            // 2. Actualizar estado a Entregado con fecha de entrega
            $stmt_upd = $this->conn->prepare("UPDATE reparacion SET estado = 'Entregado', fecha_entrega = CURRENT_TIMESTAMP WHERE id_reparacion = ?");
            $stmt_upd->bind_param("i", $id_reparacion);
            if (!$stmt_upd->execute()) {
                throw new Exception("Error al actualizar estado de la reparación.");
            }
            $stmt_upd->close();

            // 3. Registrar en historial
            $nota = 'Cobrado y entregado desde Ventas';
            $estado_nuevo = 'Entregado';
            $stmt_hist = $this->conn->prepare("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt_hist->bind_param("isssi", $id_reparacion, $old_state, $estado_nuevo, $nota, $id_usuario);
            $stmt_hist->execute();
            $stmt_hist->close();

            // 4. Crear registro en venta
            $descripcion = "Cobro de Reparación #" . $repair['codigo_orden'] . " - " . $repair['equipo_marca'] . " " . $repair['equipo_modelo'];
            $fecha = date('Y-m-d H:i:s');
            $stmt_vta = $this->conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago, id_usuario, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt_vta->bind_param("sdsis", $fecha, $monto_total, $metodo_pago, $id_usuario, $descripcion);
            if (!$stmt_vta->execute()) {
                throw new Exception("Error al registrar la venta del cobro.");
            }
            $id_venta = $stmt_vta->insert_id;
            $stmt_vta->close();

            // 5. Crear Detalle de Venta (Servicio Técnico Taller)
            $id_producto_servicio = $this->get_or_ensure_service_product();
            $nombre_prod = "Servicio Técnico Taller";
            $stmt_det = $this->conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto, precio_copiado) VALUES (?, ?, 1, ?, ?, ?)");
            $stmt_det->bind_param("iidsd", $id_venta, $id_producto_servicio, $monto_total, $nombre_prod, $monto_total);
            $stmt_det->execute();
            $stmt_det->close();

            $this->conn->commit();
            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Asegura la existencia del producto de servicio técnico para cumplir la clave foránea de detalle_venta
    public function get_or_ensure_service_product(): int
    {
        // Se identifica por nombre (nunca por un ID fijo, que puede pertenecer a un producto físico)
        $existing = ServiceProduct::findId($this->conn);
        if ($existing !== null) {
            return $existing;
        }

        $name = ServiceProduct::NAME;
        $stmt = $this->conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, 0.00, 1)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $new_id = (int)$stmt->insert_id;
        $stmt->close();

        $stmt_d = $this->conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria, descripcion) VALUES (?, 'Nokia', 'Taller', 'Servicios', 'Servicio Técnico y Mano de Obra Taller')");
        $stmt_d->bind_param("i", $new_id);
        $stmt_d->execute();
        $stmt_d->close();

        $stmt_i = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, 9999)");
        $stmt_i->bind_param("i", $new_id);
        $stmt_i->execute();
        $stmt_i->close();

        return $new_id;
    }

    public function register_repair_payment(int $id_reparacion, float $monto, string $metodo_pago, int $id_usuario)
    {
        $id_reparacion = intval($id_reparacion);
        $monto = floatval($monto);
        $id_usuario = intval($id_usuario);
        
        // Obtener detalles de la reparación
        $repair = $this->find_repair_by_id($id_reparacion);
        if (!$repair) throw new Exception("Reparación no encontrada");

        $descripcion = "Cobro de Reparación #" . $repair['codigo_orden'] . " - " . $repair['equipo_marca'] . " " . $repair['equipo_modelo'];
        $fecha = date('Y-m-d H:i:s');

        $this->conn->begin_transaction();
        try {
            // 1. Crear Venta
            $stmt = $this->conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago, id_usuario, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsis", $fecha, $monto, $metodo_pago, $id_usuario, $descripcion);
            if (!$stmt->execute()) throw new Exception("Error al registrar el ingreso");
            $id_venta = $stmt->insert_id;
            $stmt->close();

            // 2. Crear Detalle de Venta
            $id_producto_servicio = $this->get_or_ensure_service_product();
            $stmt = $this->conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto, precio_copiado) VALUES (?, ?, 1, ?, ?, ?)");
            $nombre_prod = "Servicio Técnico Taller";
            $stmt->bind_param("iidsd", $id_venta, $id_producto_servicio, $monto, $nombre_prod, $monto);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function add_repair_image(int $id_reparacion, string $ruta_archivo, string $tipo = 'Ingreso')
    {
        $id_reparacion = intval($id_reparacion);
        
        $sql = "INSERT INTO reparacion_imagen (id_reparacion, ruta_archivo, tipo) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $id_reparacion, $ruta_archivo, $tipo);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function get_repair_images(int $id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT id_imagen, ruta_archivo, tipo, fecha_subida FROM reparacion_imagen WHERE id_reparacion = ? ORDER BY fecha_subida ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_reparacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
        }
        $stmt->close();
        return $images;
    }

    public function delete_repair_image(int $id_imagen, int $id_reparacion)
    {
        $id_imagen = intval($id_imagen);
        $id_reparacion = intval($id_reparacion);
        
        $stmt = $this->conn->prepare("SELECT ruta_archivo FROM reparacion_imagen WHERE id_imagen = ? AND id_reparacion = ?");
        $stmt->bind_param("ii", $id_imagen, $id_reparacion);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $ruta = $row['ruta_archivo'];
            $stmt->close();
            
            $stmt_del = $this->conn->prepare("DELETE FROM reparacion_imagen WHERE id_imagen = ? AND id_reparacion = ?");
            $stmt_del->bind_param("ii", $id_imagen, $id_reparacion);
            $success = $stmt_del->execute();
            $stmt_del->close();
            if ($success) {
                return $ruta;
            }
        } else {
            $stmt->close();
        }
        return false;
    }

    public function get_repair_status_stats()
    {
        $sql = "SELECT estado, COUNT(*) as total
                FROM reparacion
                GROUP BY estado
                ORDER BY FIELD(estado, 'Recibido','En diagnóstico','En reparación','Listo','Entregado','Cancelado')";
        $result = $this->conn->query($sql);
        $labels = [];
        $counts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['estado'];
                $counts[] = (int)$row['total'];
            }
        }
        return ['labels' => $labels, 'counts' => $counts];
    }

    public function get_repairs_by_brand()
    {
        $sql = "SELECT equipo_marca, COUNT(*) as total
                FROM reparacion
                GROUP BY equipo_marca
                ORDER BY total DESC
                LIMIT 5";
        $result = $this->conn->query($sql);
        $labels = [];
        $counts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['equipo_marca'];
                $counts[] = (int)$row['total'];
            }
        }
        return ['labels' => $labels, 'counts' => $counts];
    }

    public function get_all_repairs_for_report()
    {
        $sql = "SELECT r.*, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                ORDER BY r.fecha_ingreso DESC";
        $result = $this->conn->query($sql);
        $repairs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $repairs[] = $row;
            }
        }
        return $repairs;
    }
}

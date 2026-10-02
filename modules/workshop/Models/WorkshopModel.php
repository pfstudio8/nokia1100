<?php
// modules/workshop/Models/WorkshopModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class WorkshopModel extends BaseModel
{
    public function get_repairs($status = '', $search = '', $limit = 50, $offset = 0)
    {
        $where_parts = [];
        if ($status) {
            $where_parts[] = "r.estado = '" . $this->conn->real_escape_string($status) . "'";
        }
        if ($search) {
            $escaped = $this->conn->real_escape_string($search);
            $where_parts[] = "(r.codigo_orden LIKE '%$escaped%' OR c.nombre LIKE '%$escaped%' OR r.equipo_marca LIKE '%$escaped%' OR r.equipo_modelo LIKE '%$escaped%')";
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT r.id_reparacion, r.codigo_orden, c.nombre AS cliente_nombre, r.equipo_marca, r.equipo_modelo, r.estado, r.fecha_ingreso, r.presupuesto, r.costo_total 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                $where_clause 
                ORDER BY r.fecha_ingreso DESC
                LIMIT $limit OFFSET $offset";

        $result = $this->conn->query($sql);
        $repairs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $repairs[] = $row;
            }
        }
        return $repairs;
    }

    public function get_repairs_count($status = '', $search = '')
    {
        $where_parts = [];
        if ($status) {
            $where_parts[] = "r.estado = '" . $this->conn->real_escape_string($status) . "'";
        }
        if ($search) {
            $escaped = $this->conn->real_escape_string($search);
            $where_parts[] = "(r.codigo_orden LIKE '%$escaped%' OR c.nombre LIKE '%$escaped%' OR r.equipo_marca LIKE '%$escaped%' OR r.equipo_modelo LIKE '%$escaped%')";
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT COUNT(*) as total
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                $where_clause";

        $res = $this->conn->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    public function get_chargeable_repairs()
    {
        $sql = "SELECT r.id_reparacion, r.codigo_orden, c.nombre AS cliente_nombre, r.equipo_marca, r.equipo_modelo, r.estado, r.fecha_ingreso, r.presupuesto, r.costo_total, r.falla_declarada, r.observaciones 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                WHERE r.estado NOT IN ('Entregado', 'Cancelado')
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

    public function get_paid_repairs_history($limit = 50, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql = "SELECT v.id_venta, v.fecha, v.total, v.metodo_de_pago, v.descripcion, u.nombre_usuario 
                FROM venta v
                LEFT JOIN usuario u ON v.id_usuario = u.id_usuario
                WHERE v.descripcion LIKE 'Cobro de Reparación #%'
                ORDER BY v.fecha DESC
                LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);
        $history = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
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

    public function find_repair_by_id($id)
    {
        $id = intval($id);
        $sql = "SELECT r.*, c.nombre AS cliente_nombre, c.telefono AS cliente_telefono, c.email AS cliente_email 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                WHERE r.id_reparacion = $id";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    public function find_repair_by_code($code)
    {
        $code = $this->conn->real_escape_string($code);
        $sql = "SELECT codigo_orden, equipo_marca, equipo_modelo, estado FROM reparacion WHERE codigo_orden = '$code'";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function get_repair_repuestos($id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT rr.*, p.nombre 
                FROM reparacion_repuesto rr 
                JOIN producto p ON rr.id_producto = p.id_producto 
                WHERE rr.id_reparacion = $id_reparacion";
        $result = $this->conn->query($sql);
        $repuestos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $repuestos[] = $row;
            }
        }
        return $repuestos;
    }

    public function get_repair_historial($id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT h.*, u.nombre_usuario 
                FROM reparacion_historial h 
                LEFT JOIN usuario u ON h.id_usuario = u.id_usuario 
                WHERE id_reparacion = $id_reparacion 
                ORDER BY fecha_cambio DESC";
        $result = $this->conn->query($sql);
        $historial = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $historial[] = $row;
            }
        }
        return $historial;
    }

    public function get_active_products_in_stock()
    {
        $sql = "SELECT p.id_producto, p.nombre, p.precio, i.cantidad 
                FROM producto p 
                JOIN inventario i ON p.id_producto = i.id_producto 
                WHERE p.is_active = 1 AND i.cantidad > 0 AND p.id_producto != 13
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

    public function create_repair_order($id_cliente_existente, $client_name, $client_phone, $client_email, $brand, $model, $imei, $failure, $notes, $budget, $user_id)
    {
        $id_cliente_existente = intval($id_cliente_existente);
        $client_name = $this->conn->real_escape_string($client_name);
        $client_phone = $this->conn->real_escape_string($client_phone);
        $client_email = $this->conn->real_escape_string($client_email);
        $brand = $this->conn->real_escape_string($brand);
        $model = $this->conn->real_escape_string($model);
        $imei = $this->conn->real_escape_string($imei);
        $failure = $this->conn->real_escape_string($failure);
        $notes = $this->conn->real_escape_string($notes);
        $budget_val = !empty($budget) ? (float)$budget : "NULL";
        $user_id = intval($user_id);

        $codigo_orden = date('ym') . mt_rand(1000, 9999);

        if ($id_cliente_existente > 0) {
            $id_cliente = $id_cliente_existente;
        } else {
            // Buscar cliente existente o crear uno nuevo
            $res_client = $this->conn->query("SELECT id_cliente FROM cliente WHERE nombre = '$client_name' AND telefono = '$client_phone'");
            if ($res_client && $res_client->num_rows > 0) {
                $id_cliente = $res_client->fetch_assoc()['id_cliente'];
            } else {
                $this->conn->query("INSERT INTO cliente (nombre, telefono, email) VALUES ('$client_name', '$client_phone', '$client_email')");
                $id_cliente = $this->conn->insert_id;
            }
        }

        $sql = "INSERT INTO reparacion (codigo_orden, id_cliente,
                equipo_marca, equipo_modelo, equipo_imei, falla_declarada, observaciones,
                presupuesto, id_usuario_recibe) 
                VALUES ('$codigo_orden', $id_cliente, 
                '$brand', '$model', '$imei', '$failure', '$notes',
                $budget_val, $user_id)";

        if ($this->conn->query($sql) === TRUE) {
            $id_reparacion = $this->conn->insert_id;

            // Historial
            $this->conn->query("INSERT INTO reparacion_historial (id_reparacion, estado_nuevo, nota, id_usuario) 
                                VALUES ($id_reparacion, 'Recibido', 'Ingreso inicial del equipo', $user_id)");

            return $id_reparacion;
        } else {
            throw new Exception("Error al crear la orden: " . $this->conn->error);
        }
    }

    public function update_repair_status_and_budget($id_reparacion, $status, $budget, $note, $user_id)
    {
        $id_reparacion = intval($id_reparacion);
        $status = $this->conn->real_escape_string($status);
        $budget_val = !empty($budget) ? (float)$budget : "NULL";
        $note = $this->conn->real_escape_string($note);
        $user_id = intval($user_id);

        // Get old status
        $res_old = $this->conn->query("SELECT estado FROM reparacion WHERE id_reparacion = $id_reparacion");
        if (!$res_old || $res_old->num_rows === 0) {
            throw new Exception("Orden de reparación no encontrada");
        }
        $old_state = $res_old->fetch_assoc()['estado'];

        $sql_update = "UPDATE reparacion SET estado = '$status', presupuesto = $budget_val";
        if ($status === 'Entregado' && $old_state !== 'Entregado') {
            $sql_update .= ", fecha_entrega = CURRENT_TIMESTAMP";
        }
        $sql_update .= " WHERE id_reparacion = $id_reparacion";

        if ($this->conn->query($sql_update) === TRUE) {
            if ($old_state !== $status || !empty($note)) {
                $this->conn->query("INSERT INTO reparacion_historial (id_reparacion, estado_anterior, estado_nuevo, nota, id_usuario) 
                                    VALUES ($id_reparacion, '$old_state', '$status', '$note', $user_id)");
            }
            return true;
        } else {
            throw new Exception("Error al actualizar la orden: " . $this->conn->error);
        }
    }

    public function add_repuesto_to_repair($id_reparacion, $id_producto, $quantity = 1)
    {
        $id_reparacion = intval($id_reparacion);
        $id_producto = intval($id_producto);
        $quantity = intval($quantity);

        // Verifica el stock disponible
        $res_inv = $this->conn->query("SELECT cantidad FROM inventario WHERE id_producto = $id_producto");
        $row_inv = $res_inv ? $res_inv->fetch_assoc() : null;

        if ($row_inv && $row_inv['cantidad'] >= $quantity) {
            $res_prod = $this->conn->query("SELECT precio FROM producto WHERE id_producto = $id_producto");
            $precio = $res_prod->fetch_assoc()['precio'];

            $this->conn->begin_transaction();
            try {
                // Agrega el repuesto a la tabla de reparaciones
                $this->conn->query("INSERT INTO reparacion_repuesto (id_reparacion, id_producto, cantidad, precio_unitario) 
                                    VALUES ($id_reparacion, $id_producto, $quantity, $precio)");

                // Resta la cantidad del inventario
                $this->conn->query("UPDATE inventario SET cantidad = cantidad - $quantity WHERE id_producto = $id_producto");

                // Actualiza el costo total de la reparación
                $this->conn->query("UPDATE reparacion SET costo_total = costo_total + ($precio * $quantity) WHERE id_reparacion = $id_reparacion");

                $this->conn->commit();
                return true;
            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }
        } else {
            throw new Exception("No hay stock suficiente de ese repuesto.");
        }
    }

    public function remove_repuesto_from_repair($id_reparacion_repuesto)
    {
        $id_reparacion_repuesto = intval($id_reparacion_repuesto);

        $res = $this->conn->query("SELECT id_reparacion, id_producto, cantidad, precio_unitario FROM reparacion_repuesto WHERE id_reparacion_repuesto = $id_reparacion_repuesto");
        $row = $res ? $res->fetch_assoc() : null;

        if ($row) {
            $this->conn->begin_transaction();
            try {
                // Eliminar el registro
                $this->conn->query("DELETE FROM reparacion_repuesto WHERE id_reparacion_repuesto = $id_reparacion_repuesto");

                // Restaurar inventario
                $this->conn->query("UPDATE inventario SET cantidad = cantidad + " . $row['cantidad'] . " WHERE id_producto = " . $row['id_producto']);

                // Restar del costo total
                $costo = $row['cantidad'] * $row['precio_unitario'];
                $this->conn->query("UPDATE reparacion SET costo_total = costo_total - $costo WHERE id_reparacion = " . $row['id_reparacion']);

                $this->conn->commit();
                return true;
            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }
        }
        return false;
    }

    public function register_repair_payment($id_reparacion, $monto, $metodo_pago, $id_usuario)
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

            // 2. Crear Detalle de Venta (Usando el producto Genérico ID 13)
            // Verificar si el ID 13 existe, sino usar NULL o un producto genérico
            $id_producto_servicio = 13; // El que creamos recientemente
            
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

    public function add_repair_image($id_reparacion, $ruta_archivo, $tipo = 'Ingreso')
    {
        $id_reparacion = intval($id_reparacion);
        $ruta_archivo = $this->conn->real_escape_string($ruta_archivo);
        $tipo = $this->conn->real_escape_string($tipo);
        
        $sql = "INSERT INTO reparacion_imagen (id_reparacion, ruta_archivo, tipo) VALUES ($id_reparacion, '$ruta_archivo', '$tipo')";
        return $this->conn->query($sql);
    }

    public function get_repair_images($id_reparacion)
    {
        $id_reparacion = intval($id_reparacion);
        $sql = "SELECT id_imagen, ruta_archivo, tipo, fecha_subida FROM reparacion_imagen WHERE id_reparacion = $id_reparacion ORDER BY fecha_subida ASC";
        $result = $this->conn->query($sql);
        $images = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
        }
        return $images;
    }

    public function delete_repair_image($id_imagen, $id_reparacion)
    {
        $id_imagen = intval($id_imagen);
        $id_reparacion = intval($id_reparacion);
        
        $sql = "SELECT ruta_archivo FROM reparacion_imagen WHERE id_imagen = $id_imagen AND id_reparacion = $id_reparacion";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $ruta = $row['ruta_archivo'];
            
            $sql_del = "DELETE FROM reparacion_imagen WHERE id_imagen = $id_imagen AND id_reparacion = $id_reparacion";
            if ($this->conn->query($sql_del)) {
                return $ruta;
            }
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
?>


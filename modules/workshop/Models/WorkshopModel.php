<?php
// modules/workshop/Models/WorkshopModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class WorkshopModel extends BaseModel
{
    public function get_repairs($status = '', $search = '')
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

        $sql = "SELECT r.id_reparacion, r.codigo_orden, c.nombre AS cliente_nombre, r.equipo_marca, r.equipo_modelo, r.estado, r.fecha_ingreso, r.presupuesto 
                FROM reparacion r
                LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
                $where_clause 
                ORDER BY r.fecha_ingreso DESC";

        $result = $this->conn->query($sql);
        $repairs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $repairs[] = $row;
            }
        }
        return $repairs;
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
                WHERE p.is_active = 1 AND i.cantidad > 0 
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
}
?>

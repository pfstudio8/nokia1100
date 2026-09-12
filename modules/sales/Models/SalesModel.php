<?php
// modules/sales/Models/SalesModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class SalesModel extends BaseModel
{
    public function get_sales()
    {
        $sql = "SELECT 
                    v.id_venta, 
                    v.fecha, 
                    v.total, 
                    v.metodo_de_pago,
                    v.estado,
                    COALESCE(dv.nombre_producto, p.nombre) AS producto,
                    dv.cantidad
                FROM venta v
                INNER JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                LEFT JOIN producto p ON dv.id_producto = p.id_producto
                ORDER BY v.fecha DESC";
        $result = $this->conn->query($sql);
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    }

    public function find_sale_by_id($id)
    {
        $stmt = $this->conn->prepare("SELECT id_venta, fecha, total, metodo_de_pago FROM venta WHERE id_venta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function get_sale_details($id_venta)
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
                WHERE i.cantidad > 0 AND p.is_active = 1
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
                GROUP BY DATE(fecha) 
                ORDER BY sale_date ASC 
                LIMIT 30";
        $result = $this->conn->query($sql);
        $dates = [];
        $totals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $dates[] = $row['sale_date'];
                $totals[] = $row['daily_total'];
            }
        }
        return ['dates' => $dates, 'totals' => $totals];
    }

    public function get_sales_by_payment_methods()
    {
        $sql = "SELECT metodo_de_pago, SUM(total) as amount 
                FROM venta 
                GROUP BY metodo_de_pago";
        $result = $this->conn->query($sql);
        $methods = [];
        $amounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $methods[] = empty($row['metodo_de_pago']) ? 'Otro' : $row['metodo_de_pago'];
                $amounts[] = $row['amount'];
            }
        }
        return ['methods' => $methods, 'amounts' => $amounts];
    }

    public function get_top_products($limit = 5)
    {
        $limit = (int) $limit;
        $sql = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                FROM detalle_venta dv
                JOIN venta v ON dv.id_venta = v.id_venta
                JOIN producto p ON dv.id_producto = p.id_producto
                WHERE v.estado = 'completada'
                GROUP BY dv.id_producto
                ORDER BY total_vendido DESC
                LIMIT $limit";
        $result = $this->conn->query($sql);
        $names = [];
        $quantities = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $names[] = $row['nombre'];
                $quantities[] = $row['total_vendido'];
            }
        }
        return ['names' => $names, 'quantities' => $quantities];
    }

    public function get_sales_export_data()
    {
        $sql = "SELECT 
                    v.id_venta, 
                    v.fecha, 
                    v.total, 
                    v.metodo_de_pago,
                    v.estado,
                    COALESCE(dv.nombre_producto, p.nombre) AS producto,
                    dv.cantidad
                FROM venta v
                LEFT JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                LEFT JOIN producto p ON dv.id_producto = p.id_producto
                ORDER BY v.fecha DESC";
        $result = $this->conn->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function create_sale_transaction($items, $metodo_pago, $user_id, $descripcion = '')
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

            foreach ($items as $item) {
                $stmt = $this->conn->prepare(
                    "SELECT precio, cantidad FROM inventario i
                     JOIN producto p ON i.id_producto = p.id_producto
                     WHERE p.id_producto = ? FOR UPDATE"
                );
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows === 0) throw new Exception("Producto ID " . $item['id'] . " no encontrado");
                $prod = $res->fetch_assoc();
                $stmt->close();

                if ($prod['cantidad'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto ID " . $item['id']);
                }
                $total_venta += $prod['precio'] * $item['cantidad'];
            }

            $stmt = $this->conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago, id_usuario, descripcion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsis", $fecha, $total_venta, $metodo_pago, $user_id, $descripcion);
            if (!$stmt->execute()) throw new Exception("Error al crear venta");
            $id_venta = $this->conn->insert_id;
            $stmt->close();

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
                    "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, nombre_producto, precio_copiado)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("iiidsd", $id_venta, $item['id'], $item['cantidad'], $precio_unitario, $nombre_producto, $precio_unitario);
                if (!$stmt->execute()) throw new Exception("Error al crear detalle");
                $stmt->close();

                $stmt = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE id_producto = ?");
                $stmt->bind_param("ii", $item['cantidad'], $item['id']);
                if (!$stmt->execute()) throw new Exception("Error al actualizar stock");
                $stmt->close();

                // Registrar en la auditoría el descuento de stock para el módulo de Inventario
                audit_log($this->conn, 'INVENTORY_UPDATE', (int)$user_id, 'Inventario', $item['id'],
                    "Stock de '$nombre_producto' reducido en {$item['cantidad']} unidades por venta (Usuario: $username_audit)");
            }

            $this->conn->commit();

            $productos_str = implode(", ", $nombres_vendidos);

            audit_log($this->conn, 'SALE_CREATE', (int)$user_id, 'venta', $id_venta,
                "Usuario $username_audit registró venta. Total: \$$total_venta. Método: $metodo_pago. Productos: $productos_str");

            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function rollback_sale($id_venta, $user_id)
    {
        $this->conn->begin_transaction();
        try {
            // Verificar estado actual
            $stmt = $this->conn->prepare("SELECT estado FROM venta WHERE id_venta = ? FOR UPDATE");
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

            // Devolver stock
            $stmt = $this->conn->prepare("SELECT d.id_producto, d.cantidad, d.nombre_producto FROM detalle_venta d WHERE d.id_venta = ?");
            $stmt->bind_param("i", $id_venta);
            $stmt->execute();
            $detalles = $stmt->get_result();
            $items_revertidos = 0;
            
            require_once __DIR__ . '/../../../config/audit.php';
            
            while ($row = $detalles->fetch_assoc()) {
                $stmt_upd = $this->conn->prepare("UPDATE inventario SET cantidad = cantidad + ? WHERE id_producto = ?");
                $stmt_upd->bind_param("ii", $row['cantidad'], $row['id_producto']);
                if (!$stmt_upd->execute()) throw new Exception("Error al devolver el stock al inventario");
                $stmt_upd->close();
                
                // Registrar en auditoría el retorno de stock
                audit_log($this->conn, 'INVENTORY_UPDATE', (int)$user_id, 'Inventario', $row['id_producto'],
                    "Stock de '{$row['nombre_producto']}' devuelto ({$row['cantidad']} unidades) por anulación de venta (Usuario: $username_audit)");
                
                $items_revertidos++;
            }
            $stmt->close();

            $this->conn->commit();

            audit_log($this->conn, 'SALE_ROLLBACK', (int)$user_id, 'venta', $id_venta,
                "Venta anulada por usuario $username_audit. Stock devuelto para $items_revertidos producto(s).");

            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>

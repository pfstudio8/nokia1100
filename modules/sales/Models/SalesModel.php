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

    public function get_sales_export_data()
    {
        $sql = "SELECT 
                    v.id_venta, 
                    v.fecha, 
                    v.total, 
                    v.metodo_de_pago,
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

    public function create_sale_transaction($items, $metodo_pago, $user_id)
    {
        $this->conn->begin_transaction();
        try {
            $total_venta = 0;
            $fecha = date('Y-m-d H:i:s');

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

            $stmt = $this->conn->prepare("INSERT INTO venta (fecha, total, metodo_de_pago) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $fecha, $total_venta, $metodo_pago);
            if (!$stmt->execute()) throw new Exception("Error al crear venta");
            $id_venta = $this->conn->insert_id;
            $stmt->close();

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
            }

            $this->conn->commit();

            require_once __DIR__ . '/../../../config/audit.php';
            audit_log($this->conn, 'SALE_CREATE', (int)$user_id, 'venta', $id_venta,
                "Venta registrada. Total: \$$total_venta. Método: $metodo_pago");

            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>

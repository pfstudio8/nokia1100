<?php
// modules/suppliers/Models/SupplierModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class SupplierModel extends BaseModel
{
    public function get_suppliers()
    {
        $result = $this->conn->query("SELECT * FROM proveedor ORDER BY nombre ASC");
        $suppliers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
        }
        return $suppliers;
    }

    public function find_supplier_by_id($id)
    {
        $stmt = $this->conn->prepare("SELECT nombre, domicilio, telefono, atencion, email FROM proveedor WHERE id_proveedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function count_supplier_purchases($id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM compra WHERE id_proveedor = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ? $res['total'] : 0;
    }

    public function create_supplier($nombre, $domicilio, $telefono, $atencion, $email)
    {
        $stmt = $this->conn->prepare("INSERT INTO proveedor (nombre, domicilio, telefono, atencion, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $domicilio, $telefono, $atencion, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update_supplier($id, $nombre, $domicilio, $telefono, $atencion, $email)
    {
        $stmt = $this->conn->prepare("UPDATE proveedor SET nombre = ?, domicilio = ?, telefono = ?, atencion = ?, email = ? WHERE id_proveedor = ?");
        $stmt->bind_param("sssssi", $nombre, $domicilio, $telefono, $atencion, $email, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete_supplier($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM proveedor WHERE id_proveedor = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function get_purchases()
    {
        $sql = "SELECT c.*, p.nombre as proveedor_nombre,
                (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
                FROM compra c
                JOIN proveedor p ON c.id_proveedor = p.id_proveedor
                ORDER BY c.fecha DESC";
        $result = $this->conn->query($sql);
        $purchases = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $purchases[] = $row;
            }
        }
        return $purchases;
    }

    public function get_purchase_details($id_compra)
    {
        $stmt = $this->conn->prepare("SELECT dc.*, pch.nombre_producto, pch.marca, pch.modelo
            FROM detalle_compra dc
            LEFT JOIN producto_compra_historial pch ON dc.id_detalle_compra = pch.id_detalle_compra
            WHERE dc.id_compra = ?");
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }

    public function get_purchase_details_by_supplier($id_proveedor)
    {
        $stmt = $this->conn->prepare("SELECT c.*, 
            (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
            FROM compra c 
            WHERE c.id_proveedor = ? 
            ORDER BY c.fecha DESC");
        $stmt->bind_param("i", $id_proveedor);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchases = [];
        while ($row = $result->fetch_assoc()) {
            $purchases[] = $row;
        }
        $stmt->close();
        return $purchases;
    }

    public function create_purchase_transaction($id_proveedor, $items, $descripcion, $tiempo_entrega, $iva, $autorizado_por)
    {
        $fecha = date('Y-m-d H:i:s');
        $total_compra = 0;

        $this->conn->begin_transaction();
        try {
            // Calcula el costo total de la compra
            foreach ($items as $item) {
                $total_compra += $item['costo'] * $item['cantidad'];
            }

            $stmt = $this->conn->prepare("INSERT INTO compra (id_proveedor, fecha, total, descripcion, tiempo_entrega, iva, autorizado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdssds", $id_proveedor, $fecha, $total_compra, $descripcion, $tiempo_entrega, $iva, $autorizado_por);
            if (!$stmt->execute()) throw new Exception("Error al crear compra");
            $id_compra = $this->conn->insert_id;
            $stmt->close();

            foreach ($items as $item) {
                $id_producto = null;

                // Verifica si el producto ya existe por nombre, marca y modelo
                $stmt_check = $this->conn->prepare("SELECT p.id_producto FROM producto p 
                                       JOIN producto_detalle d ON p.id_producto = d.id_producto 
                                       WHERE p.nombre = ? AND d.marca = ? AND d.modelo = ?");
                $stmt_check->bind_param("sss", $item['nombre'], $item['marca'], $item['modelo']);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if ($res_check) {
                    $id_producto = $res_check['id_producto'];
                } else {
                    $stmt_prod = $this->conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
                    $stmt_prod->bind_param("sd", $item['nombre'], $item['costo']);
                    if (!$stmt_prod->execute()) throw new Exception("Error al crear producto");
                    $id_producto = $this->conn->insert_id;
                    $stmt_prod->close();

                    $stmt_det = $this->conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo) VALUES (?, ?, ?)");
                    $stmt_det->bind_param("iss", $id_producto, $item['marca'], $item['modelo']);
                    if (!$stmt_det->execute()) throw new Exception("Error al crear detalle de producto");
                    $stmt_det->close();
                }

                $stmt_inv = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
                $stmt_inv->bind_param("iii", $id_producto, $item['cantidad'], $item['cantidad']);
                if (!$stmt_inv->execute()) throw new Exception("Error al actualizar inventario");
                $stmt_inv->close();

                $stmt_dc = $this->conn->prepare("INSERT INTO detalle_compra (id_compra, id_producto, cantidad, precio_compra) VALUES (?, ?, ?, ?)");
                $stmt_dc->bind_param("iiid", $id_compra, $id_producto, $item['cantidad'], $item['costo']);
                if (!$stmt_dc->execute()) throw new Exception("Error al crear detalle de compra");
                $id_detalle_compra = $this->conn->insert_id;
                $stmt_dc->close();

                $stmt_hist = $this->conn->prepare("INSERT INTO producto_compra_historial (id_detalle_compra, nombre_producto, marca, modelo) VALUES (?, ?, ?, ?)");
                $stmt_hist->bind_param("isss", $id_detalle_compra, $item['nombre'], $item['marca'], $item['modelo']);
                if (!$stmt_hist->execute()) throw new Exception("Error al crear historial de compra");
                $stmt_hist->close();
            }

            $this->conn->commit();
            return $id_compra;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>

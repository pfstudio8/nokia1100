<?php
// modules/inventory/Models/InventoryModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class InventoryModel extends BaseModel
{
    public function get_inventory($search = '', $filter = '')
    {
        $where_parts = [];
        if ($search) {
            $search_esc = $this->conn->real_escape_string($search);
            $where_parts[] = "(p.nombre LIKE '%$search_esc%' OR d.marca LIKE '%$search_esc%' OR d.modelo LIKE '%$search_esc%')";
        }
        if ($filter === 'low_stock') {
            $where_parts[] = "i.cantidad <= 5";
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, i.cantidad, p.precio, p.is_active
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                $where_clause
                ORDER BY p.nombre ASC";

        $result = $this->conn->query($sql);
        $items = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    public function find_product_by_id($id_producto)
    {
        $stmt = $this->conn->prepare("
            SELECT p.nombre, p.precio, d.marca, d.modelo, i.cantidad 
            FROM producto p
            JOIN producto_detalle d ON p.id_producto = d.id_producto
            JOIN inventario i ON p.id_producto = i.id_producto
            WHERE p.id_producto = ?
        ");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function check_product_sales_purchases($id_producto)
    {
        $stmt = $this->conn->prepare("SELECT 
            (SELECT COUNT(*) FROM detalle_venta WHERE id_producto = ?) + 
            (SELECT COUNT(*) FROM detalle_compra WHERE id_producto = ?) AS total");
        $stmt->bind_param("ii", $id_producto, $id_producto);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return $total;
    }

    public function create_product_transaction($product_data, $detail_data, $cantidad)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
            $stmt->bind_param("sd", $product_data['nombre'], $product_data['precio']);
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar producto base");
            }
            $id_producto = $this->conn->insert_id;
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria, codigo, descripcion, stock_minimo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "isssssi",
                $id_producto,
                $detail_data['marca'],
                $detail_data['modelo'],
                $detail_data['categoria'],
                $detail_data['codigo'],
                $detail_data['descripcion'],
                $detail_data['stock_minimo']
            );
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar detalles del producto");
            }
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_producto, $cantidad);
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar el stock inicial");
            }
            $stmt->close();

            $this->conn->commit();
            return $id_producto;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function update_product_transaction($id_producto, $product_data, $detail_data, $cantidad)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE producto SET nombre = ?, precio = ? WHERE id_producto = ?");
            $stmt->bind_param("sdi", $product_data['nombre'], $product_data['precio'], $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al actualizar producto");
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE producto_detalle SET marca = ?, modelo = ? WHERE id_producto = ?");
            $stmt->bind_param("ssi", $detail_data['marca'], $detail_data['modelo'], $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al actualizar detalles");
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE inventario SET cantidad = ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al actualizar inventario");
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function toggle_product_status($id_producto)
    {
        // Prepara la sentencia SQL para negar (toggle/invertir) el estado lógico de 'is_active' del producto
        $stmt = $this->conn->prepare("UPDATE producto SET is_active = NOT is_active WHERE id_producto = ?");
        // Vincula el ID del producto como un entero al parámetro de la consulta
        $stmt->bind_param("i", $id_producto);
        // Ejecuta la consulta preparada y almacena si fue exitosa
        $success = $stmt->execute();
        // Cierra la sentencia de base de datos
        $stmt->close();
        // Retorna el resultado de la ejecución (true en caso de éxito, false en error)
        return $success;
    }

    public function deactivate_product_and_delete_stock($id_producto)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE producto SET is_active = 0 WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al desactivar el producto");
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM inventario WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al eliminar stock");
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function delete_product_completely($id_producto)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("DELETE FROM inventario WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al eliminar de inventario");
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM producto_detalle WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al eliminar detalles");
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM producto WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            if (!$stmt->execute())
                throw new Exception("Error al eliminar producto");
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function get_purchase_history($id_producto)
    {
        $id_producto = intval($id_producto);
        $sql = "SELECT c.fecha, pr.nombre as proveedor, dc.cantidad, dc.precio_compra, 
                       (dc.cantidad * dc.precio_compra) as subtotal,
                       c.descripcion, c.autorizado_por
                FROM detalle_compra dc
                JOIN compra c ON dc.id_compra = c.id_compra
                JOIN proveedor pr ON c.id_proveedor = pr.id_proveedor
                WHERE dc.id_producto = ?
                ORDER BY c.fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchases = [];
        while ($row = $result->fetch_assoc()) {
            $purchases[] = $row;
        }
        $stmt->close();
        return $purchases;
    }

    public function update_images_db()
    {
        // iPhones
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%iPhone%' OR marca = 'Apple'");

        // Samsung
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%Samsung%' OR marca = 'Samsung'");

        // Motorola (Generic Smartphone)
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1598327105666-5b89351aff23?auto=format&fit=crop&q=80&w=400' WHERE modelo LIKE '%Motorola%' OR marca = 'Motorola'");

        // Accessories - Charger
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&q=80&w=400' WHERE tipo_repuesto LIKE '%Cargador%' OR modelo LIKE '%Cargador%'");

        // Accessories - Case/Funda
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1586105251261-72a756497a11?auto=format&fit=crop&q=80&w=400' WHERE tipo_repuesto LIKE '%Funda%' OR modelo LIKE '%Funda%'");

        // Generic fallback for others
        $this->conn->query("UPDATE producto_detalle SET imagen_url = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&q=80&w=400' WHERE imagen_url IS NULL OR imagen_url LIKE '%via.placeholder%'");

        return true;
    }
}
?>
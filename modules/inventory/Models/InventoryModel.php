<?php
// modules/inventory/Models/InventoryModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class InventoryModel extends BaseModel
{
    public function get_inventory($search = '', $filter = '', $limit = 50, $offset = 0)
    {
        $where_parts = [];
        $types = "";
        $params = [];

        if ($search) {
            $search_wildcard = "%" . $search . "%";
            $where_parts[] = "(p.nombre LIKE ? OR d.marca LIKE ? OR d.modelo LIKE ?)";
            $types .= "sss";
            array_push($params, $search_wildcard, $search_wildcard, $search_wildcard);
        }
        if ($filter === 'low_stock') {
            $where_parts[] = "i.cantidad <= d.stock_minimo";
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, i.cantidad, p.precio, p.is_active
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                $where_clause
                ORDER BY p.nombre ASC
                LIMIT ? OFFSET ?";

        $types .= "ii";
        array_push($params, $limit, $offset);

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $stmt->close();
        return $items;
    }

    public function get_inventory_count($search = '', $filter = '')
    {
        $where_parts = [];
        $types = "";
        $params = [];

        if ($search) {
            $search_wildcard = "%" . $search . "%";
            $where_parts[] = "(p.nombre LIKE ? OR d.marca LIKE ? OR d.modelo LIKE ?)";
            $types .= "sss";
            array_push($params, $search_wildcard, $search_wildcard, $search_wildcard);
        }
        if ($filter === 'low_stock') {
            $where_parts[] = "i.cantidad <= d.stock_minimo";
        }

        $where_clause = count($where_parts) > 0 ? " WHERE " . implode(" AND ", $where_parts) : "";

        $sql = "SELECT COUNT(*) as total
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                $where_clause";

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        
        $total = 0;
        if ($res && $row = $res->fetch_assoc()) {
            $total = (int)$row['total'];
        }
        $stmt->close();
        return $total;
    }

    public function find_product_by_id(int $id_producto)
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

    public function check_product_sales_purchases(int $id_producto)
    {
        $stmt = $this->conn->prepare("SELECT 
            (SELECT COUNT(*) FROM detalle_venta WHERE id_producto = ?) + 
            (SELECT COUNT(*) FROM detalle_compra WHERE id_producto = ?) AS total");
        $stmt->bind_param("ii", $id_producto, $id_producto);
        $stmt->execute();
        $total = 0;
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return $total;
    }

    public function create_product_transaction(array $product_data, array $detail_data, int $cantidad)
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

    public function update_product_transaction(int $id_producto, array $product_data, array $detail_data, int $cantidad)
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

    public function toggle_product_status(int $id_producto)
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

    public function deactivate_product_and_delete_stock(int $id_producto)
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

    public function delete_product_completely(int $id_producto)
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

    public function get_purchase_history(int $id_producto)
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


    public function get_critical_stock_details()
    {
        $sql = "SELECT p.nombre, i.cantidad, pd.stock_minimo, pd.marca, pd.modelo 
                FROM inventario i
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                JOIN producto p ON i.id_producto = p.id_producto
                WHERE p.is_active = 1 AND i.cantidad <= pd.stock_minimo
                ORDER BY i.cantidad ASC";
        $result = $this->conn->query($sql);
        $products = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }

    public function get_stock_stats()
    {
        $sql = "SELECT 
                SUM(CASE WHEN i.cantidad > pd.stock_minimo THEN 1 ELSE 0 END) as sano,
                SUM(CASE WHEN i.cantidad > 0 AND i.cantidad <= pd.stock_minimo THEN 1 ELSE 0 END) as bajo,
                SUM(CASE WHEN i.cantidad = 0 THEN 1 ELSE 0 END) as agotado
                FROM inventario i
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                JOIN producto p ON i.id_producto = p.id_producto
                WHERE p.is_active = 1";
        $result = $this->conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return [
                'sano' => (int)$row['sano'],
                'bajo' => (int)$row['bajo'],
                'agotado' => (int)$row['agotado']
            ];
        }
        return ['sano' => 0, 'bajo' => 0, 'agotado' => 0];
    }
    
    public function get_stock_by_brand()
    {
        $sql = "SELECT COALESCE(pd.marca, 'Otros') as marca, SUM(i.cantidad) as total_stock
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                WHERE p.is_active = 1
                GROUP BY pd.marca
                ORDER BY total_stock DESC
                LIMIT 5";
        $result = $this->conn->query($sql);
        $labels = [];
        $values = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['marca'];
                $values[] = (int)$row['total_stock'];
            }
        }
        return ['labels' => $labels, 'values' => $values];
    }

    public function get_top_value_products($limit = 5)
    {
        $limit = intval($limit);
        $sql = "SELECT TRIM(CONCAT(d.marca, ' ', d.modelo)) as product_name, i.cantidad as stock_quantity
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE p.is_active = 1 AND i.cantidad > 0
                ORDER BY i.cantidad DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        $values = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row['product_name'];
                $values[] = $row['stock_quantity'];
            }
        }
        $stmt->close();
        return ['products' => $products, 'values' => $values];
    }
}
?>



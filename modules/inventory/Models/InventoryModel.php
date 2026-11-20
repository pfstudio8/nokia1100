<?php
// modules/inventory/Models/InventoryModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';
require_once __DIR__ . '/../../../classes/ServiceProduct.php';

class InventoryModel extends BaseModel
{
    public function get_all_products_catalog()
    {
        $excludeServices = ServiceProduct::excludeSql('p');
        $sql = "SELECT p.id_producto, p.nombre, d.marca, d.modelo, d.categoria, d.codigo, d.stock_minimo, i.cantidad, p.precio, p.is_active
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE (d.categoria IS NULL OR d.categoria != 'Servicios') AND $excludeServices
                ORDER BY p.nombre ASC";
        $result = $this->conn->query($sql);
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    public function get_inventory_stats()
    {
        $excludeServices = ServiceProduct::excludeSql('p');
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN i.cantidad <= d.stock_minimo AND i.cantidad > 0 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN i.cantidad = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    COALESCE(SUM(i.cantidad * p.precio), 0) as total_value
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE (d.categoria IS NULL OR d.categoria != 'Servicios') AND $excludeServices";
        $res = $this->conn->query($sql);
        $data = $res ? $res->fetch_assoc() : null;
        return [
            'total_count' => (int)($data['total_count'] ?? 0),
            'low_stock' => (int)($data['low_stock'] ?? 0),
            'out_of_stock' => (int)($data['out_of_stock'] ?? 0),
            'total_value' => (float)($data['total_value'] ?? 0)
        ];
    }

    public function get_all_categories()
    {
        $sql = "SELECT DISTINCT categoria FROM producto_detalle WHERE categoria IS NOT NULL AND categoria != '' AND categoria != 'Servicios' ORDER BY categoria ASC";
        $result = $this->conn->query($sql);
        $cats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cats[] = $row['categoria'];
            }
        }
        return $cats;
    }

    public function find_product_by_id(int $id_producto)
    {
        $stmt = $this->conn->prepare("
            SELECT p.id_producto, p.nombre, p.precio, d.marca, d.modelo, d.categoria, d.codigo, d.stock_minimo, i.cantidad 
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
            $is_active = isset($product_data['is_active']) ? (int)$product_data['is_active'] : null;
            if ($is_active !== null) {
                $stmt = $this->conn->prepare("UPDATE producto SET nombre = ?, precio = ?, is_active = ? WHERE id_producto = ?");
                $stmt->bind_param("sdii", $product_data['nombre'], $product_data['precio'], $is_active, $id_producto);
            } else {
                $stmt = $this->conn->prepare("UPDATE producto SET nombre = ?, precio = ? WHERE id_producto = ?");
                $stmt->bind_param("sdi", $product_data['nombre'], $product_data['precio'], $id_producto);
            }
            if (!$stmt->execute())
                throw new Exception("Error al actualizar producto");
            $stmt->close();

            $marca = $detail_data['marca'] ?? '';
            $modelo = $detail_data['modelo'] ?? '';
            $categoria = $detail_data['categoria'] ?? null;
            $codigo = $detail_data['codigo'] ?? null;
            $stock_minimo = isset($detail_data['stock_minimo']) ? (int)$detail_data['stock_minimo'] : null;

            if ($codigo !== null && $stock_minimo !== null && $categoria !== null) {
                $stmt = $this->conn->prepare("UPDATE producto_detalle SET marca = ?, modelo = ?, categoria = ?, codigo = ?, stock_minimo = ? WHERE id_producto = ?");
                $stmt->bind_param("ssssii", $marca, $modelo, $categoria, $codigo, $stock_minimo, $id_producto);
            } elseif ($categoria !== null) {
                $stmt = $this->conn->prepare("UPDATE producto_detalle SET marca = ?, modelo = ?, categoria = ? WHERE id_producto = ?");
                $stmt->bind_param("sssi", $marca, $modelo, $categoria, $id_producto);
            } else {
                $stmt = $this->conn->prepare("UPDATE producto_detalle SET marca = ?, modelo = ? WHERE id_producto = ?");
                $stmt->bind_param("ssi", $marca, $modelo, $id_producto);
            }
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

    public function deactivate_product(int $id_producto)
    {
        $stmt = $this->conn->prepare("UPDATE producto SET is_active = 0 WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $success = $stmt->execute();
        $stmt->close();
        if (!$success) {
            throw new Exception("Error al desactivar el producto");
        }
        return true;
    }

    public function get_critical_stock_details()
    {
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT p.nombre, i.cantidad, pd.stock_minimo, pd.marca, pd.modelo 
                FROM inventario i
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                JOIN producto p ON i.id_producto = p.id_producto
                WHERE p.is_active = 1 AND i.cantidad <= pd.stock_minimo AND $excludeServices
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
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT 
                SUM(CASE WHEN i.cantidad > pd.stock_minimo THEN 1 ELSE 0 END) as sano,
                SUM(CASE WHEN i.cantidad > 0 AND i.cantidad <= pd.stock_minimo THEN 1 ELSE 0 END) as bajo,
                SUM(CASE WHEN i.cantidad = 0 THEN 1 ELSE 0 END) as agotado
                FROM inventario i
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                JOIN producto p ON i.id_producto = p.id_producto
                WHERE p.is_active = 1 AND $excludeServices";
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
        $excludeServices = ServiceProduct::excludeSql('p', 'pd');
        $sql = "SELECT COALESCE(NULLIF(TRIM(pd.marca), ''), 'Otros') as marca, SUM(i.cantidad) as total_stock
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle pd ON i.id_producto = pd.id_producto
                WHERE p.is_active = 1 AND $excludeServices
                GROUP BY COALESCE(NULLIF(TRIM(pd.marca), ''), 'Otros')
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
        $excludeServices = ServiceProduct::excludeSql('p', 'd');
        $sql = "SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(d.marca, ''), ' ', COALESCE(d.modelo, ''))), ''), p.nombre) as product_name,
                       i.cantidad as stock_quantity
                FROM inventario i
                JOIN producto p ON i.id_producto = p.id_producto
                JOIN producto_detalle d ON p.id_producto = d.id_producto
                WHERE p.is_active = 1 AND i.cantidad > 0 AND $excludeServices
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
                $values[] = (int)$row['stock_quantity'];
            }
        }
        $stmt->close();
        return ['products' => $products, 'values' => $values];
    }
}



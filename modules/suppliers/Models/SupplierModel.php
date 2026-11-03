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
        $stmt = $this->conn->prepare("SELECT id_proveedor, nombre, domicilio, telefono, atencion, email FROM proveedor WHERE id_proveedor = ?");
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
        return $res ? (int) $res['total'] : 0;
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
        $sql = "SELECT c.*, p.nombre as proveedor_nombre, p.telefono as proveedor_telefono, p.atencion as proveedor_atencion,
                (SELECT COUNT(*) FROM detalle_compra WHERE id_compra = c.id_compra) as items
                FROM compra c
                JOIN proveedor p ON c.id_proveedor = p.id_proveedor
                ORDER BY c.fecha DESC, c.id_compra DESC";
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
        $stmt = $this->conn->prepare("SELECT dc.*, 
                COALESCE(pch.nombre_producto, p.nombre, 'Artículo') AS nombre_producto, 
                COALESCE(pch.marca, pd.marca, '') AS marca, 
                COALESCE(pch.modelo, pd.modelo, '') AS modelo,
                COALESCE(pd.codigo, '') AS sku
            FROM detalle_compra dc
            LEFT JOIN producto_compra_historial pch ON dc.id_detalle_compra = pch.id_detalle_compra
            LEFT JOIN producto p ON dc.id_producto = p.id_producto
            LEFT JOIN producto_detalle pd ON dc.id_producto = pd.id_producto
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
            ORDER BY c.fecha DESC, c.id_compra DESC");
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

    public function quick_create_product(string $sku, string $nombre, string $marca, string $modelo, float $costo, int $stock_minimo = 3, string $categoria = 'Accesorios')
    {
        $this->conn->begin_transaction();
        try {
            $stmt_prod = $this->conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
            $stmt_prod->bind_param("sd", $nombre, $costo);
            if (!$stmt_prod->execute()) {
                throw new Exception("Error al crear el producto en catálogo");
            }
            $id_producto = (int) $this->conn->insert_id;
            $stmt_prod->close();

            if ($sku === '') {
                $sku = 'SKU-' . str_pad((string) $id_producto, 4, '0', STR_PAD_LEFT);
            }

            $descripcion = '';
            $stmt_det = $this->conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria, codigo, descripcion, stock_minimo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_det->bind_param("isssssi", $id_producto, $marca, $modelo, $categoria, $sku, $descripcion, $stock_minimo);
            if (!$stmt_det->execute()) {
                throw new Exception("Error al guardar detalles del producto");
            }
            $stmt_det->close();

            $stmt_inv = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, 0)");
            $stmt_inv->bind_param("i", $id_producto);
            if (!$stmt_inv->execute()) {
                throw new Exception("Error al inicializar stock del producto");
            }
            $stmt_inv->close();

            $this->conn->commit();
            return [
                'id_producto' => $id_producto,
                'sku' => $sku,
                'nombre' => $nombre,
                'marca' => $marca,
                'modelo' => $modelo,
                'categoria' => $categoria,
                'cantidad' => 0,
                'stock_minimo' => $stock_minimo,
                'precio' => $costo,
                'is_active' => 1
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function create_purchase_transaction($id_proveedor, $items, $descripcion, $tiempo_entrega, $iva, $autorizado_por)
    {
        $fecha = date('Y-m-d H:i:s');
        $total_compra = 0;

        $this->conn->begin_transaction();
        try {
            // Calcula el costo total de la compra
            foreach ($items as $item) {
                $qty = max(1, (int) ($item['cantidad'] ?? 1));
                $cost = max(0, (float) ($item['costo'] ?? 0));
                $total_compra += $cost * $qty;
            }

            $stmt = $this->conn->prepare("INSERT INTO compra (id_proveedor, fecha, total, descripcion, tiempo_entrega, iva, autorizado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdssds", $id_proveedor, $fecha, $total_compra, $descripcion, $tiempo_entrega, $iva, $autorizado_por);
            if (!$stmt->execute())
                throw new Exception("Error al crear compra");
            $id_compra = (int) $this->conn->insert_id;
            $stmt->close();

            foreach ($items as $item) {
                $id_producto = !empty($item['id_producto']) ? (int) $item['id_producto'] : null;
                $nombre = trim((string) ($item['nombre'] ?? ''));
                $marca = trim((string) ($item['marca'] ?? ''));
                $modelo = trim((string) ($item['modelo'] ?? ''));
                $sku = trim((string) ($item['sku'] ?? ''));
                $costo = max(0, (float) ($item['costo'] ?? 0));
                $cantidad = max(1, (int) ($item['cantidad'] ?? 1));

                if ($id_producto) {
                    // Obtener datos del producto existente si faltan nombre/marca/modelo
                    $stmt_find = $this->conn->prepare("SELECT p.nombre, d.marca, d.modelo FROM producto p LEFT JOIN producto_detalle d ON p.id_producto = d.id_producto WHERE p.id_producto = ?");
                    $stmt_find->bind_param("i", $id_producto);
                    $stmt_find->execute();
                    $prod_row = $stmt_find->get_result()->fetch_assoc();
                    $stmt_find->close();
                    if ($prod_row) {
                        if ($nombre === '')
                            $nombre = (string) $prod_row['nombre'];
                        if ($marca === '')
                            $marca = (string) ($prod_row['marca'] ?? '');
                        if ($modelo === '')
                            $modelo = (string) ($prod_row['modelo'] ?? '');
                    }
                } else {
                    // Verifica si el producto ya existe por nombre, marca y modelo
                    $stmt_check = $this->conn->prepare("SELECT p.id_producto FROM producto p 
                                           JOIN producto_detalle d ON p.id_producto = d.id_producto 
                                           WHERE p.nombre = ? AND d.marca = ? AND d.modelo = ?");
                    $stmt_check->bind_param("sss", $nombre, $marca, $modelo);
                    $stmt_check->execute();
                    $res_check = $stmt_check->get_result()->fetch_assoc();
                    $stmt_check->close();

                    if ($res_check) {
                        $id_producto = (int) $res_check['id_producto'];
                    } else {
                        $stmt_prod = $this->conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
                        $stmt_prod->bind_param("sd", $nombre, $costo);
                        if (!$stmt_prod->execute())
                            throw new Exception("Error al crear producto");
                        $id_producto = (int) $this->conn->insert_id;
                        $stmt_prod->close();

                        if ($sku === '') {
                            $sku = 'SKU-' . str_pad((string) $id_producto, 4, '0', STR_PAD_LEFT);
                        }
                        $cat = 'Accesorios';
                        $desc_prod = '';
                        $min_stock = 3;
                        $stmt_det = $this->conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo, categoria, codigo, descripcion, stock_minimo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt_det->bind_param("isssssi", $id_producto, $marca, $modelo, $cat, $sku, $desc_prod, $min_stock);
                        if (!$stmt_det->execute())
                            throw new Exception("Error al crear detalle de producto");
                        $stmt_det->close();

                        $stmt_inv_init = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, 0)");
                        $stmt_inv_init->bind_param("i", $id_producto);
                        if (!$stmt_inv_init->execute())
                            throw new Exception("Error al inicializar inventario del producto");
                        $stmt_inv_init->close();
                    }
                }

                // NO actualizamos el inventario aquí, el pedido queda 'Pendiente'
                // El inventario se actualizará cuando se llame a receive_purchase()

                $stmt_dc = $this->conn->prepare("INSERT INTO detalle_compra (id_compra, id_producto, cantidad, precio_compra) VALUES (?, ?, ?, ?)");
                $stmt_dc->bind_param("iiid", $id_compra, $id_producto, $cantidad, $costo);
                if (!$stmt_dc->execute())
                    throw new Exception("Error al crear detalle de compra");
                $id_detalle_compra = (int) $this->conn->insert_id;
                $stmt_dc->close();

                $stmt_hist = $this->conn->prepare("INSERT INTO producto_compra_historial (id_detalle_compra, nombre_producto, marca, modelo) VALUES (?, ?, ?, ?)");
                $stmt_hist->bind_param("isss", $id_detalle_compra, $nombre, $marca, $modelo);
                if (!$stmt_hist->execute())
                    throw new Exception("Error al crear historial de compra");
                $stmt_hist->close();
            }

            $this->conn->commit();
            return $id_compra;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function receive_purchase($id_compra, array $adjusted_items = [])
    {
        $this->conn->begin_transaction();
        try {
            // Verificar estado actual
            $stmt = $this->conn->prepare("SELECT estado FROM compra WHERE id_compra = ? FOR UPDATE");
            $stmt->bind_param("i", $id_compra);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$res || $res['estado'] !== 'Pendiente') {
                throw new Exception("El pedido no se encuentra en estado Pendiente.");
            }

            // Mapa opcional de ajustes enviados desde el modal de Control de Recepción
            $adjustMapByDetail = [];
            $adjustMapByProd = [];
            foreach ($adjusted_items as $adj) {
                if (!empty($adj['id_detalle_compra'])) {
                    $adjustMapByDetail[(int) $adj['id_detalle_compra']] = $adj;
                }
                if (!empty($adj['id_producto'])) {
                    $adjustMapByProd[(int) $adj['id_producto']] = $adj;
                }
            }

            $items = $this->get_purchase_details($id_compra);
            $new_total = 0.0;

            foreach ($items as $item) {
                $id_det = (int) $item['id_detalle_compra'];
                $id_prod = (int) $item['id_producto'];
                $qty_real = (int) $item['cantidad'];
                $cost_real = (float) $item['precio_compra'];

                $adj = $adjustMapByDetail[$id_det] ?? ($adjustMapByProd[$id_prod] ?? null);
                if ($adj !== null) {
                    if (isset($adj['recibido'])) {
                        $qty_real = max(0, (int) $adj['recibido']);
                    } elseif (isset($adj['cantidad'])) {
                        $qty_real = max(0, (int) $adj['cantidad']);
                    }
                    if (isset($adj['costo'])) {
                        $cost_real = max(0.0, (float) $adj['costo']);
                    }
                    // Actualizar detalle_compra con lo realmente recibido y liquidado
                    $stmt_upd_dc = $this->conn->prepare("UPDATE detalle_compra SET cantidad = ?, precio_compra = ? WHERE id_detalle_compra = ?");
                    $stmt_upd_dc->bind_param("idi", $qty_real, $cost_real, $id_det);
                    $stmt_upd_dc->execute();
                    $stmt_upd_dc->close();
                }

                $new_total += ($qty_real * $cost_real);

                if ($qty_real > 0 && $id_prod > 0) {
                    $stmt_inv = $this->conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, ?) 
                                           ON DUPLICATE KEY UPDATE cantidad = cantidad + ?");
                    $stmt_inv->bind_param("iii", $id_prod, $qty_real, $qty_real);
                    if (!$stmt_inv->execute())
                        throw new Exception("Error al actualizar inventario");
                    $stmt_inv->close();
                }
            }

            // Actualizar estado y total liquidado de la compra
            if (!empty($adjusted_items)) {
                $stmt = $this->conn->prepare("UPDATE compra SET estado = 'Recibido', total = ? WHERE id_compra = ?");
                $stmt->bind_param("di", $new_total, $id_compra);
            } else {
                $stmt = $this->conn->prepare("UPDATE compra SET estado = 'Recibido' WHERE id_compra = ?");
                $stmt->bind_param("i", $id_compra);
            }
            if (!$stmt->execute())
                throw new Exception("Error al actualizar estado de compra");
            $stmt->close();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function cancel_purchase($id_compra)
    {
        $stmt = $this->conn->prepare("UPDATE compra SET estado = 'Cancelado' WHERE id_compra = ? AND estado = 'Pendiente'");
        $stmt->bind_param("i", $id_compra);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
}

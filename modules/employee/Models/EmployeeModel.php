<?php
// modules/employee/Models/EmployeeModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class EmployeeModel extends BaseModel
{
    public function get_daily_sales_stats(string $date): array
    {
        $stmt = $this->conn->prepare("SELECT SUM(total) as total_hoy, COUNT(*) as transacciones FROM venta WHERE DATE(fecha) = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_assoc() : ['total_hoy' => 0, 'transacciones' => 0];
        $stmt->close();
        return [
            'total_hoy' => $data['total_hoy'] ? (float)$data['total_hoy'] : 0.0,
            'transacciones' => $data['transacciones'] ? (int)$data['transacciones'] : 0
        ];
    }

    public function get_recent_sales(int $limit = 5): array
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("SELECT id_venta, fecha, total, metodo_de_pago FROM venta ORDER BY fecha DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        $stmt->close();
        return $sales;
    }

    public function get_featured_products(int $limit = 4): array
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("
            SELECT p.id_producto, p.nombre, p.precio,
                   COALESCE(d.marca, '') as marca,
                   COALESCE(d.modelo, '') as modelo,
                   COALESCE(d.categoria, 'General') as categoria,
                   COALESCE(i.cantidad, 0) as stock
            FROM producto p
            LEFT JOIN producto_detalle d ON p.id_producto = d.id_producto
            LEFT JOIN inventario i ON p.id_producto = i.id_producto
            WHERE p.is_active = 1
            ORDER BY i.cantidad DESC, p.id_producto ASC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        $stmt->close();
        return $products;
    }
}

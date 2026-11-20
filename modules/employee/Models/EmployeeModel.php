<?php
// modules/employee/Models/EmployeeModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';
require_once __DIR__ . '/../../../classes/ServiceProduct.php';

class EmployeeModel extends BaseModel
{
    public function get_daily_sales_stats(string $date): array
    {
        $stmt = $this->conn->prepare("SELECT SUM(total) as total_hoy, COUNT(*) as transacciones FROM venta WHERE DATE(fecha) = ? AND estado != 'anulada'");
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
        require_once __DIR__ . '/../../admin/Models/AdminModel.php';
        $adminModel = new AdminModel();
        return $adminModel->get_recent_sales($limit);
    }

    public function get_featured_products(int $limit = 4): array
    {
        $limit = (int) $limit;
        $excludeServices = ServiceProduct::excludeSql('p', 'd');
        $stmt = $this->conn->prepare("
            SELECT p.id_producto, p.nombre, p.precio,
                   COALESCE(d.marca, '') as marca,
                   COALESCE(d.modelo, '') as modelo,
                   COALESCE(d.categoria, 'General') as categoria,
                   COALESCE(d.stock_minimo, 5) as stock_minimo,
                   COALESCE(i.cantidad, 0) as stock
            FROM producto p
            LEFT JOIN producto_detalle d ON p.id_producto = d.id_producto
            LEFT JOIN inventario i ON p.id_producto = i.id_producto
            WHERE p.is_active = 1 AND $excludeServices
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

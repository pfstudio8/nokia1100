<?php
// modules/employee/Models/EmployeeModel.php

require_once __DIR__ . '/../../../classes/BaseModel.php';

class EmployeeModel extends BaseModel
{
    public function get_daily_sales_stats($date)
    {
        $res = $this->conn->query("SELECT SUM(total) as total_hoy, COUNT(*) as transacciones FROM venta WHERE DATE(fecha) = '$date'");
        $data = $res ? $res->fetch_assoc() : ['total_hoy' => 0, 'transacciones' => 0];
        return [
            'total_hoy' => $data['total_hoy'] ? $data['total_hoy'] : 0,
            'transacciones' => $data['transacciones'] ? $data['transacciones'] : 0
        ];
    }

    public function get_recent_sales($limit = 5)
    {
        $limit = (int) $limit;
        $result = $this->conn->query("SELECT id_venta, fecha, total, metodo_de_pago FROM venta ORDER BY fecha DESC LIMIT $limit");
        $sales = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    }

    public function get_featured_products($limit = 4)
    {
        $limit = (int) $limit;
        $result = $this->conn->query("SELECT id_producto, nombre, precio FROM producto WHERE is_active = 1 LIMIT $limit");
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }
}
?>

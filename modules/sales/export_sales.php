<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ventas_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM for right encoding in Excel
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['ID Venta', 'Fecha', 'Total ($)', 'Metodo de Pago', 'Producto', 'Cantidad']);

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
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_venta'],
            $row['fecha'],
            number_format($row['total'], 2, '.', ''),
            $row['metodo_de_pago'],
            $row['producto'] ? $row['producto'] : 'Desconocido',
            $row['cantidad'] ? $row['cantidad'] : 1
        ]);
    }
}
fclose($output);
exit;

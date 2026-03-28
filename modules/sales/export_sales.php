<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=ventas_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

$sql = "SELECT id_venta, fecha, total, metodo_de_pago FROM venta ORDER BY fecha DESC";
$result = $conn->query($sql);

echo "<table border='1'>";
echo "<tr>
        <th>ID Venta</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Método de Pago</th>
      </tr>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_venta']}</td>
                <td>{$row['fecha']}</td>
                <td>{$row['total']}</td>
                <td>{$row['metodo_de_pago']}</td>
              </tr>";
    }
}

echo "</table>";
exit;
?>

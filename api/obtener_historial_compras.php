<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}
require_once '../config/bd.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit();
}

// Get purchase history for this product
$sql = "SELECT c.fecha, pr.nombre as proveedor, dc.cantidad, dc.precio_compra, 
               (dc.cantidad * dc.precio_compra) as subtotal,
               c.descripcion, c.autorizado_por
        FROM detalle_compra dc
        JOIN compra c ON dc.id_compra = c.id_compra
        JOIN proveedor pr ON c.id_proveedor = pr.id_proveedor
        WHERE dc.id_producto = ?
        ORDER BY c.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();

$purchases = [];
while ($row = $result->fetch_assoc()) {
    $purchases[] = [
        'fecha' => $row['fecha'],
        'proveedor' => $row['proveedor'],
        'cantidad' => $row['cantidad'],
        'precio_compra' => $row['precio_compra'],
        'subtotal' => $row['subtotal'],
        'descripcion' => $row['descripcion'],
        'autorizado_por' => $row['autorizado_por']
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'purchases' => $purchases]);
?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}
require_once __DIR__ . '/../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['nombre']) || !isset($input['precio'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$nombre = $input['nombre'];
$marca = $input['marca'] ?? '';
$modelo = $input['modelo'] ?? '';
$precio = floatval($input['precio']);

$conn->begin_transaction();
try {
    // 1. Insert producto
    $stmt = $conn->prepare("INSERT INTO producto (nombre, precio, is_active) VALUES (?, ?, 1)");
    $stmt->bind_param("sd", $nombre, $precio);
    if (!$stmt->execute()) throw new Exception("Error al crear producto");
    $id_producto = $conn->insert_id;
    $stmt->close();

    // 2. Insert producto_detalle
    $stmt = $conn->prepare("INSERT INTO producto_detalle (id_producto, marca, modelo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_producto, $marca, $modelo);
    if (!$stmt->execute()) throw new Exception("Error al crear detalles");
    $stmt->close();

    // 3. Insert inventario with 0 initial stock
    $stmt = $conn->prepare("INSERT INTO inventario (id_producto, cantidad) VALUES (?, 0)");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al crear inventario");
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'id_producto' => $id_producto, 'message' => 'Producto creado']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

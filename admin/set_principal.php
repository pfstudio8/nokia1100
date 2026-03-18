<?php
require_once __DIR__ . '/../includes/auth.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}
require_once '../config/bd.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_imagen']) && isset($_POST['id_producto'])) {
    $id_imagen = intval($_POST['id_imagen']);
    $id_producto = intval($_POST['id_producto']);
    
    // Resetear todas las de este producto
    $conn->query("UPDATE producto_imagen SET es_principal = 0 WHERE id_producto = $id_producto");
    
    // Establecer nueva principal
    $stmt = $conn->prepare("UPDATE producto_imagen SET es_principal = 1 WHERE id_imagen = ? AND id_producto = ?");
    $stmt->bind_param("ii", $id_imagen, $id_producto);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
}
?>

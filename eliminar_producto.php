<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: inventario.php");
    exit();
}
require_once 'config/bd.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto === 0) {
    header("Location: inventario.php");
    exit();
}

$conn->begin_transaction();

try {
    // 1. Delete from inventario
    $stmt = $conn->prepare("DELETE FROM inventario WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar de inventario");
    $stmt->close();

    // 2. Delete from producto_detalle
    $stmt = $conn->prepare("DELETE FROM producto_detalle WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar detalles");
    $stmt->close();

    // 3. Delete from producto
    $stmt = $conn->prepare("DELETE FROM producto WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar producto");
    $stmt->close();

    $conn->commit();
    header("Location: inventario.php?msg=deleted");
} catch (Exception $e) {
    $conn->rollback();
    // In a real app, you might want to log this error or show it to the user
    // For now, we'll redirect with an error flag
    header("Location: inventario.php?error=" . urlencode($e->getMessage()));
}
exit();
?>

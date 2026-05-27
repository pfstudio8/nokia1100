<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto === 0) {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
    exit();
}

// Verificar si el producto tiene ventas o compras asociadas
$stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM detalle_venta WHERE id_producto = ?) + 
    (SELECT COUNT(*) FROM detalle_compra WHERE id_producto = ?) AS total");
$stmt->bind_param("ii", $id_producto, $id_producto);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

if ($total > 0) {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php?error=has_sales");
    exit();
}

$conn->begin_transaction();

try {
    // Borro primero la cantidad de stock que tiene este producto del inventario
    $stmt = $conn->prepare("DELETE FROM inventario WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar de inventario");
    $stmt->close();

    // Elimino la marca, modelo y otros detalles específicos del producto
    $stmt = $conn->prepare("DELETE FROM producto_detalle WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar detalles");
    $stmt->close();

    // Finalmente borro el producto de la tabla principal para que no quede rastro
    $stmt = $conn->prepare("DELETE FROM producto WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) throw new Exception("Error al eliminar producto");
    $stmt->close();

    $conn->commit();
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php?success=deleted");
} catch (Exception $e) {
    $conn->rollback();
    // In a real app, you might want to log this error or show it to the user
    // For now, we'll redirect with an error flag
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php?error=" . urlencode($e->getMessage()));
}
exit();
?>

<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto > 0) {
    // Toggle status
    $sql = "UPDATE producto SET is_active = NOT is_active WHERE id_producto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    
    if ($stmt->execute()) {
        header("Location: " . BASE_URL . "/modules/inventory/inventory.php?msg=status_updated");
    } else {
        header("Location: " . BASE_URL . "/modules/inventory/inventory.php?error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: " . BASE_URL . "/modules/inventory/inventory.php");
}
exit();
?>

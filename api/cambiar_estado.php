<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/../config/bd.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto > 0) {
    // Toggle status
    $sql = "UPDATE producto SET is_active = NOT is_active WHERE id_producto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    
    if ($stmt->execute()) {
        header("Location: ../admin/inventario/inventario.php?msg=status_updated");
    } else {
        header("Location: ../admin/inventario/inventario.php?error=update_failed");
    }
    $stmt->close();
} else {
    header("Location: ../admin/inventario/inventario.php");
}
exit();
?>

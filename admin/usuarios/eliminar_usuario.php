<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin_auth();

if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: ../panel_empleado.php");
    exit();
}

// Verificar conexión
require_once "../../config/bd.php";

// Verificar que haya un id recibido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_usuarios.php?error=No_id");
    exit();
}

$id = intval($_GET['id']);

// Proteger al admin principal (si querés evitar borrar al id 1)
if ($id == 1) {
    header("Location: admin_usuarios.php?error=No_puedes_eliminar_admin");
    exit();
}

// Eliminar el usuario
$sql = "DELETE FROM usuarios_admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: admin_usuarios.php?success=Usuario_eliminado");
} else {
    header("Location: admin_usuarios.php?error=Error_al_eliminar");
}

$stmt->close();
$conn->close();
?>

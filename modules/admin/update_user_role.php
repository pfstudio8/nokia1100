<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/modules/admin/users.php");
    exit();
}

$id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;
$rol = $_POST['rol'] ?? '';

if ($id_usuario <= 0 || !in_array($rol, ['admin', 'empleado'], true)) {
    header("Location: " . BASE_URL . "/modules/admin/users.php?error=Datos inválidos para actualizar el rol");
    exit();
}

if ($id_usuario === (int) $_SESSION['user_id'] && $rol !== 'admin') {
    header("Location: " . BASE_URL . "/modules/admin/users.php?error=No puedes quitarte tu propio rol de administrador");
    exit();
}

$stmt = $conn->prepare("UPDATE usuario SET rol = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $rol, $id_usuario);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    header("Location: " . BASE_URL . "/modules/admin/users.php?success=Rol actualizado correctamente");
} else {
    header("Location: " . BASE_URL . "/modules/admin/users.php?error=No se pudo actualizar el rol");
}

$stmt->close();
exit();
?>

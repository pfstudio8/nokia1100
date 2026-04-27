<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (empty($token) || empty($password) || empty($password_confirm)) {
    header("Location: " . BASE_URL . "/modules/auth/reset_password.php?token=" . urlencode($token) . "&error=Por favor complete todos los campos");
    exit();
}

if ($password !== $password_confirm) {
    header("Location: " . BASE_URL . "/modules/auth/reset_password.php?token=" . urlencode($token) . "&error=Las contraseñas no coinciden");
    exit();
}

// Validar que la contraseña tenga una longitud mínima (ej. 6 caracteres)
if (strlen($password) < 6) {
    header("Location: " . BASE_URL . "/modules/auth/reset_password.php?token=" . urlencode($token) . "&error=La contraseña debe tener al menos 6 caracteres");
    exit();
}

// Validar token en la base de datos
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE token_verificacion = ? AND token_expira > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: " . BASE_URL . "/modules/auth/reset_password.php?token=" . urlencode($token) . "&error=Token inválido o expirado");
    exit();
}

$row = $result->fetch_assoc();
$id_usuario = $row['id_usuario'];
$stmt->close();

// Hashear nueva contraseña
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Actualizar en base de datos y limpiar token
$updateStmt = $conn->prepare("UPDATE usuario SET contrasena = ?, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
$updateStmt->bind_param("si", $hashed_password, $id_usuario);

if ($updateStmt->execute()) {
    header("Location: " . BASE_URL . "/index.php?success=Contraseña actualizada exitosamente. Ya puedes iniciar sesión.");
} else {
    header("Location: " . BASE_URL . "/modules/auth/reset_password.php?token=" . urlencode($token) . "&error=Ocurrió un error al actualizar la contraseña");
}

$updateStmt->close();
exit();
?>

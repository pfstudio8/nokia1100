<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// If not a POST request, redirect and exit early
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: " . BASE_URL . "/index.php?error=Por favor complete todos los campos");
    exit();
}

$stmt = $conn->prepare("SELECT id_usuario, nombre_usuario, contrasena, rol FROM usuario WHERE nombre_usuario = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: " . BASE_URL . "/index.php?error=Usuario no encontrado");
    exit();
}

$row = $result->fetch_assoc();
if (!password_verify($password, $row['contrasena'])) {
    $stmt->close();
    header("Location: " . BASE_URL . "/index.php?error=Contraseña incorrecta");
    exit();
}

$_SESSION['user_id'] = $row['id_usuario'];
$_SESSION['username'] = $row['nombre_usuario'];
$_SESSION['role'] = $row['rol'];

$stmt->close();

if ($row['rol'] === 'admin') {
    header("Location: " . BASE_URL . "/modules/admin/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/modules/employee/dashboard.php");
}
exit();
?>

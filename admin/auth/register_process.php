<?php
// admin/auth/register_process.php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_admin_auth();
require_once dirname(dirname(__DIR__)) . '/config/bd.php';

// Only Admins can register new users
if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: ../login.php?error=Acceso denegado");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    if (empty($username) || empty($email) || empty($password) || empty($rol)) {
        header("Location: ../register.php?error=Completa todos los campos");
        exit();
    }

    // Check if username or email exists
    $stmt = $conn->prepare("SELECT id FROM usuarios_admin WHERE nombre_usuario = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../register.php?error=El usuario o correo ya existe");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios_admin (nombre_usuario, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $rol);

    if ($stmt->execute()) {
        header("Location: ../register.php?success=Usuario registrado correctamente");
    } else {
        header("Location: ../register.php?error=Error al registrar el usuario");
    }
} else {
    header("Location: ../register.php");
}
?>

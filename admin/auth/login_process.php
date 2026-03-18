<?php
// admin/auth/login_process.php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/bd.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=Por favor complete todos los campos");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, nombre_usuario, password, rol FROM usuarios_admin WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Session isolation: only set admin_id
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['nombre_usuario'];
            $_SESSION['admin_role'] = $user['rol'];
            
            if ($user['rol'] === 'admin') {
                header("Location: ../panel_admin.php");
            } else {
                header("Location: ../panel_empleado.php");
            }
            exit();
        } else {
            header("Location: ../login.php?error=Contraseña incorrecta");
            exit();
        }
    } else {
        header("Location: ../login.php?error=Usuario no encontrado");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>

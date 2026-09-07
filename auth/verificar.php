<?php
// verificar.php
session_start();
require_once '../config/db.php';

// Cargar la configuración base (BASE_URL) para usar redirecciones relativas correctas
// Asumiendo que BASE_URL está definido en db.php, si no:
if (!defined('BASE_URL')) {
    define('BASE_URL', '/nokia1100');
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $now = date('Y-m-d H:i:s');
    
    // Buscar usuario con token válido
    $stmt = $conn->prepare("SELECT id_usuario, nombre_usuario, rol FROM usuario WHERE token_verificacion = ? AND token_expira > ? AND verificado = 0");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $id_usuario = $user['id_usuario'];
        $username = $user['nombre_usuario'];
        $rol = $user['rol'] ?? 'empleado';
        $stmt->close();

        // Activar cuenta
        $update_stmt = $conn->prepare("UPDATE usuario SET verificado = 1, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
        $update_stmt->bind_param("i", $id_usuario);
        
        if ($update_stmt->execute()) {
            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $id_usuario;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $rol;
            
            $session_token = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $session_token;
            
            // Actualizar session_token en BD
            $stmt_tok = $conn->prepare("UPDATE usuario SET session_token = ? WHERE id_usuario = ?");
            $stmt_tok->bind_param("si", $session_token, $id_usuario);
            $stmt_tok->execute();
            $stmt_tok->close();
            
            // Auditoría
            require_once '../config/audit.php';
            audit_log($conn, 'LOGIN_OK', $id_usuario, 'usuario', $id_usuario, "Cuenta verificada y sesión iniciada automáticamente: $username");

            $msg = urlencode("¡Cuenta verificada exitosamente! Bienvenido, $username.");
            // Redirigir siempre a empleado por defecto (el admin decide luego si cambiar rol)
            header("Location: " . BASE_URL . "/modules/employee/dashboard.php?success=" . $msg);
            exit();
        } else {
            header("Location: " . BASE_URL . "/index.php?error=" . urlencode("Error al activar la cuenta en la base de datos."));
            exit();
        }
        $update_stmt->close();

    } else {
        header("Location: " . BASE_URL . "/index.php?error=" . urlencode("El enlace de verificación es inválido, ha expirado o tu cuenta ya fue activada."));
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
?>

<?php
// verificar.php
require_once '../config/bd.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // 1. Buscar el usuario con ese token y que no haya expirado
    $now = date('Y-m-d H:i:s');
    
    // Preparamos consulta
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE token_verificacion = ? AND token_expira > ? AND verificado = 0");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token válido -> Activar cuenta
        $user = $result->fetch_assoc();
        $id_usuario = $user['id_usuario'];
        $stmt->close();

        // Actualizar usuario: verificado=1, borrar token
        $update_stmt = $conn->prepare("UPDATE usuario SET verificado = 1, token_verificacion = NULL, token_expira = NULL WHERE id_usuario = ?");
        $update_stmt->bind_param("i", $id_usuario);
        
        if ($update_stmt->execute()) {
            $mensaje = "¡Cuenta verificada con éxito! Ahora puedes iniciar sesión.";
            $tipo = "success";
        } else {
            $mensaje = "Error al activar la cuenta.";
            $tipo = "error";
        }
        $update_stmt->close();

    } else {
        // Token inválido, expirado o ya usado
        $mensaje = "El enlace de verificación es inválido, ha expirado o la cuenta ya fue activada.";
        $tipo = "error";
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Cuenta</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .msg-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 50px auto;
        }
        .success { color: #2e7d32; }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <div class="msg-card">
        <h2 class="<?php echo $tipo; ?>"><?php echo ($tipo == 'success') ? '¡Verificado!' : 'Error'; ?></h2>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
        <a href="../index.php" class="btn">Ir al Login</a>
    </div>
</body>
</html>

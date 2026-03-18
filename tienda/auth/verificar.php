<?php
// tienda/auth/verificar.php
require_once dirname(dirname(__DIR__)) . '/config/bd.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM usuarios_tienda WHERE token_verificacion = ? AND token_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $id = $user['id'];

        $update = $conn->prepare("UPDATE usuarios_tienda SET verificado = 1, token_verificacion = NULL, token_expira = NULL WHERE id = ?");
        $update->bind_param("i", $id);
        
        if ($update->execute()) {
            header("Location: ../login.php?success=Cuenta verificada exitosamente. Ya puedes iniciar sesión.");
        } else {
            header("Location: ../login.php?error=Error al verificar la cuenta.");
        }
    } else {
        header("Location: ../login.php?error=Token inválido o expirado.");
    }
} else {
    header("Location: ../login.php");
}
?>

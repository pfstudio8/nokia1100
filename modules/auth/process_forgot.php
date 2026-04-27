<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Si no es POST, redirigir
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/forgot_password.php");
    exit();
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    header("Location: " . BASE_URL . "/forgot_password.php?error=Por favor ingrese su correo electrónico");
    exit();
}

// Buscar el usuario por email de la tabla persona
$stmt = $conn->prepare("
    SELECT u.id_usuario, p.nombre 
    FROM usuario u 
    JOIN persona p ON u.id_persona = p.id_persona 
    WHERE p.email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $id_usuario = $row['id_usuario'];
    $nombre = $row['nombre'];

    // Generar token
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en BD
    $updateStmt = $conn->prepare("UPDATE usuario SET token_verificacion = ?, token_expira = ? WHERE id_usuario = ?");
    $updateStmt->bind_param("ssi", $token, $expira, $id_usuario);
    $updateStmt->execute();
    $updateStmt->close();

    // Link de recuperación
    $resetLink = BASE_URL . "/modules/auth/reset_password.php?token=" . $token;

    // Aquí iría el código real de PHPMailer si estuviera disponible en el servidor.
    // Como estamos desarrollando localmente, simulamos la respuesta y pasamos el link por sesión
    // para propósitos de depuración.

    /*
    Ejemplo de uso de PHPMailer:
    require_once '../../config/config_mail.php';
    cargarPHPMailer();
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    ... configuración SMTP ...
    $mail->Subject = 'Recuperación de contraseña - Nokia 1100';
    $mail->Body    = "Hola $nombre, <br><br>Haz clic en el siguiente enlace para recuperar tu contraseña:<br><a href='$resetLink'>$resetLink</a><br><br>Si no solicitaste esto, ignora este correo.";
    $mail->send();
    */

    $_SESSION['debug_reset_link'] = $resetLink;

}

$stmt->close();

// Siempre decimos que fue exitoso para evitar enumeración de correos
header("Location: " . BASE_URL . "/forgot_password.php?success=Si el correo existe en nuestra base de datos, hemos enviado un enlace de recuperación.");
exit();
?>

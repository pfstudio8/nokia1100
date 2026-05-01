<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/config_mail.php';

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

    // Link de recuperación absoluto para correo
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $resetLink = $scheme . '://' . $host . BASE_URL . "/modules/auth/reset_password.php?token=" . $token;

    $smtpUser = trim((string) SMTP_USER);
    $smtpPass = preg_replace('/\s+/', '', (string) SMTP_PASS);
    $mailSent = false;

    if (empty($smtpUser) || empty($smtpPass)) {
        error_log('SMTP_USER/SMTP_PASS no configurados. Define variables de entorno para enviar correos.');
        $_SESSION['mail_debug_notice'] = 'No se pudo enviar el correo: faltan credenciales SMTP.';
    } elseif (cargarPHPMailer()) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña - Nokia 1100';
            $mail->Body = "
                <p>Hola {$nombre},</p>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                <p><a href=\"{$resetLink}\">Restablecer contraseña</a></p>
                <p>Este enlace vence en 1 hora.</p>
                <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
            ";
            $mail->AltBody = "Hola {$nombre},\n\nRestablece tu contraseña con este enlace: {$resetLink}\n\nEste enlace vence en 1 hora.\n\nSi no solicitaste este cambio, ignora este correo.";

            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            error_log('Error enviando correo de recuperación: ' . $mail->ErrorInfo);
            $_SESSION['mail_debug_notice'] = 'No se pudo enviar el correo. Revisa usuario/clave SMTP (App Password de Gmail).';
        }
    } else {
        error_log('PHPMailer no está disponible. Verifica la instalación.');
        $_SESSION['mail_debug_notice'] = 'PHPMailer no está instalado en el proyecto.';
    }

    if ($mailSent) {
        unset($_SESSION['mail_debug_notice']);
    }

}

$stmt->close();

// Siempre decimos que fue exitoso para evitar enumeración de correos
header("Location: " . BASE_URL . "/forgot_password.php?success=Si el correo existe en nuestra base de datos, hemos enviado un enlace de recuperación.");
exit();
?>

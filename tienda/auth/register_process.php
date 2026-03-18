<?php
// tienda/auth/register_process.php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/bd.php';
require_once dirname(dirname(__DIR__)) . '/config/config_mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: ../register.php?error=Completa todos los campos");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Las contraseñas no coinciden");
        exit();
    }

    // Check if email exists in Store table
    $stmt = $conn->prepare("SELECT id FROM usuarios_tienda WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ../register.php?error=El correo ya está registrado");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $conn->prepare("INSERT INTO usuarios_tienda (email, password, token_verificacion, token_expira) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $hashed_password, $token, $expira);

    if ($stmt->execute()) {
        // Send verification email
        if (cargarPHPMailer()) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Verifica tu cuenta - Nokia Store';
                $verify_link = "http://" . $_SERVER['HTTP_HOST'] . "/nokia1100/tienda/auth/verificar.php?token=" . $token;
                
                $mail->Body = "
                    <h1>¡Bienvenido a Nokia Store!</h1>
                    <p>Por favor, haz clic en el siguiente enlace para verificar tu cuenta:</p>
                    <a href='{$verify_link}'>Verificar mi cuenta</a>
                    <p>Este enlace expirará en 24 horas.</p>
                ";

                $mail->send();
                header("Location: ../login.php?success=Registro exitoso. Revisa tu correo para verificar tu cuenta.");
            } catch (Exception $e) {
                // User is registered but email failed - ideally log this
                header("Location: ../login.php?success=Registro exitoso, pero hubo un error enviando el correo de verificación. Contacta a soporte.");
            }
        } else {
            header("Location: ../login.php?success=Registro exitoso (PHPMailer not found).");
        }
    } else {
        header("Location: ../register.php?error=Error al registrar el usuario");
    }
} else {
    header("Location: ../register.php");
}
?>

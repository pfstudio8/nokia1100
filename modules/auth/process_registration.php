<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../audit.php';
require_once __DIR__ . '/../../config/config_mail.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/index.php?action=register");
    exit();
}

$nombre           = trim($_POST['nombre'] ?? '');
$apellido         = trim($_POST['apellido'] ?? '');
$dni              = trim($_POST['dni'] ?? '');
$email            = trim($_POST['email'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$direccion        = trim($_POST['direccion'] ?? '');
$username         = trim($_POST['username'] ?? '');
$password         = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

if (empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($username) || empty($password) || empty($password_confirm)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=Complete todos los campos requeridos");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=El formato del email no es válido");
    exit();
}

if ($password !== $password_confirm) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=Las contraseñas no coinciden");
    exit();
}

if (strlen($password) < 6) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=La contraseña debe tener al menos 6 caracteres");
    exit();
}

if (!preg_match('/^[0-9]{7,10}$/', $dni)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=El DNI debe tener entre 7 y 10 dígitos");
    exit();
}

if (!empty($telefono) && !preg_match('/^[0-9\s+()\-]{6,20}$/', $telefono)) {
    header("Location: " . BASE_URL . "/index.php?action=register&error=El teléfono tiene un formato inválido");
    exit();
}

$checkUser = $conn->prepare("SELECT id_usuario FROM usuario WHERE nombre_usuario = ?");
$checkUser->bind_param("s", $username);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows > 0) {
    $checkUser->close();
    header("Location: " . BASE_URL . "/index.php?action=register&error=El nombre de usuario ya está en uso");
    exit();
}
$checkUser->close();

$checkEmail = $conn->prepare("SELECT id_persona FROM persona WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    $checkEmail->close();
    header("Location: " . BASE_URL . "/index.php?action=register&error=El email ingresado ya está registrado");
    exit();
}
$checkEmail->close();

$checkDni = $conn->prepare("SELECT id_persona FROM persona WHERE dni = ?");
$checkDni->bind_param("s", $dni);
$checkDni->execute();
$checkDni->store_result();
if ($checkDni->num_rows > 0) {
    $checkDni->close();
    header("Location: " . BASE_URL . "/index.php?action=register&error=El DNI ingresado ya está registrado");
    exit();
}
$checkDni->close();

$conn->begin_transaction();

try {
    $stmtPersona = $conn->prepare("INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtPersona->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion);
    if (!$stmtPersona->execute()) {
        throw new Exception("Error al registrar persona");
    }
    $id_persona = $conn->insert_id;
    $stmtPersona->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $rol = 'empleado';
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmtUsuario = $conn->prepare("INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol, email, verificado, is_active, token_verificacion, token_expira) VALUES (?, ?, ?, ?, ?, 0, 1, ?, ?)");
    $stmtUsuario->bind_param("isssssss", $id_persona, $username, $hashedPassword, $rol, $email, $token, $expira);
    if (!$stmtUsuario->execute()) {
        throw new Exception("Error al registrar usuario");
    }
    $id_nuevo_usuario = $conn->insert_id;
    $stmtUsuario->close();

    $conn->commit();

    $mailSent = false;
    $smtpUser = trim((string) SMTP_USER);
    $smtpPass = preg_replace('/\s+/', '', (string) SMTP_PASS);
    if (!empty($smtpUser) && !empty($smtpPass) && cargarPHPMailer()) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
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
            $mail->Subject = 'Verifica tu cuenta - Nokia 1100';

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $verifyLink = $scheme . '://' . $host . BASE_URL . '/auth/verificar.php?token=' . $token;

            $mail->Body = "<p>Hola {$nombre},</p><p>Gracias por registrarte. Para activar tu cuenta haz clic en el siguiente enlace:</p><p><a href=\"{$verifyLink}\">Verificar mi email</a></p><p>Este enlace expira en 24 horas.</p>";
            $mail->AltBody = "Hola {$nombre},\n\nGracias por registrarte. Copia este enlace en tu navegador para verificar tu email: {$verifyLink}\n\nEste enlace expira en 24 horas.";
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            error_log('Error enviando correo de verificación: ' . $mail->ErrorInfo);
            $_SESSION['mail_debug_notice'] = 'No se pudo enviar el correo de verificación. Contacta al administrador.';
        }
    } else {
        $_SESSION['mail_debug_notice'] = 'No se pudo enviar el correo de verificación porque SMTP no está configurado.';
    }

    $id_ejecutor = $_SESSION['user_id'] ?? null;
    audit_log($conn, 'USER_CREATE', $id_ejecutor, 'usuario', $id_nuevo_usuario, "Nuevo usuario creado: $username ($nombre $apellido)");

    $message = 'Registro exitoso. Revisa tu correo para activar tu cuenta.';
    if (!$mailSent) {
        $message = 'Registro creado. Contacta al administrador para activar tu cuenta si no recibes el email.';
    }

    header("Location: " . BASE_URL . "/index.php?success=" . urlencode($message));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: " . BASE_URL . "/index.php?action=register&error=" . urlencode($e->getMessage()));
    exit();
}
?>

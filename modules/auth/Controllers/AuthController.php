<?php
// modules/auth/Controllers/AuthController.php

require_once __DIR__ . '/../../../classes/BaseController.php';
require_once __DIR__ . '/../Models/AuthModel.php';
require_once __DIR__ . '/../../../config/audit.php';

class AuthController extends BaseController
{
    private AuthModel $auth_model;

    public function __construct()
    {
        parent::__construct();
        $this->auth_model = new AuthModel();
    }

    /**
     * URL base absoluta para enlaces enviados por correo (verificación / reset).
     * Prioriza APP_URL del .env (ej: https://midominio.com). Nunca confía en la
     * cabecera Host, que puede ser falsificada por el cliente.
     */
    private function app_url(): string
    {
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL)) {
            return $appUrl . BASE_URL;
        }

        // Fallback (solo desarrollo): usa el host del servidor, no el del cliente.
        error_log('APP_URL no está definido en .env: se usa SERVER_NAME como respaldo.');
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return $scheme . '://' . $host . BASE_URL;
    }

    /**
     * Configuración SMTP ya validada y normalizada por config/config_mail.php.
     * Se lee desde $_ENV (en lugar de constantes definidas en runtime) para que
     * el código sea analizable estáticamente.
     *
     * @return array{host:string,port:int,user:string,pass:string,from_email:string,from_name:string}
     */
    private function smtp_settings(): array
    {
        require_once __DIR__ . '/../../../config/config_mail.php';

        return [
            'host'       => (string) $_ENV['SMTP_HOST'],
            'port'       => (int) $_ENV['SMTP_PORT'],
            'user'       => trim((string) $_ENV['SMTP_USER']),
            'pass'       => preg_replace('/\s+/', '', (string) $_ENV['SMTP_PASS']),
            'from_email' => (string) $_ENV['SMTP_FROM_EMAIL'],
            'from_name'  => (string) $_ENV['SMTP_FROM_NAME'],
        ];
    }

    private function is_ajax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
               (isset($_POST['ajax']) && $_POST['ajax'] === '1');
    }

    private function send_otp_email(string $email, string $nombre, string $code): bool
    {
        require_once __DIR__ . '/../../../config/config_mail.php';
        $smtp = $this->smtp_settings();
        $smtpUser = $smtp['user'];
        $smtpPass = $smtp['pass'];

        if (empty($smtpUser) || empty($smtpPass)) {
            error_log('SMTP_USER/SMTP_PASS no configurados para enviar código de verificación.');
            $_SESSION['mail_debug_notice'] = 'Credenciales SMTP vacías en config_mail.php / mail.local.php';
            return false;
        }

        if (!cargarPHPMailer()) {
            error_log('No se pudo cargar PHPMailer.');
            return false;
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtp['port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = 'Código de Verificación - Nokia 1100';

            $year = date('Y');
            $codeFormatted = substr($code, 0, 3) . ' ' . substr($code, 3, 3);

            $mail->Body = "
            <!DOCTYPE html>
            <html lang=\"es\">
            <head>
                <meta charset=\"utf-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                <title>Código de Verificación - Nokia 1100</title>
                <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
                <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
                <link href=\"https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;700;800&family=IBM+Plex+Mono:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap\" rel=\"stylesheet\">
            </head>
            <body style=\"margin: 0; padding: 0; background-color: #eeebe3; font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #15171a;\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #eeebe3; padding: 40px 16px;\">
                    <tr>
                        <td align=\"center\">
                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 480px; background-color: #f8f6f0; border: 1px solid #b5af9c; border-radius: 4px; overflow: hidden; box-shadow: 0 12px 32px -6px rgba(21, 23, 26, 0.08);\">
                                <tr>
                                    <td style=\"background-color: #111315; padding: 16px 24px; border-bottom: 2px solid #c6e24a;\">
                                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                            <tr>
                                                <td align=\"left\" style=\"vertical-align: middle;\">
                                                    <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; color: #c6e24a; text-transform: uppercase;\">
                                                        SISTEMA NOKIA
                                                    </span>
                                                    <span style=\"display: inline-block; margin-left: 6px; background-color: #c6e24a; color: #15171a; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 2px; vertical-align: middle;\">
                                                        1100
                                                    </span>
                                                </td>
                                                <td align=\"right\" style=\"vertical-align: middle;\">
                                                    <span style=\"display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #c6e24a;\"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 36px 32px 30px; text-align: left;\">
                                        <div style=\"margin-bottom: 14px;\">
                                            <span style=\"display: inline-block; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; color: #5c6165; text-transform: uppercase; border: 1px solid #d3cebf; padding: 3px 8px; border-radius: 2px; background-color: #eeebe3;\">
                                                VERIFICACIÓN DE CUENTA
                                            </span>
                                        </div>

                                        <h1 style=\"margin: 0 0 14px 0; font-family: 'Bricolage Grotesque', 'IBM Plex Sans', Arial, sans-serif; font-size: 24px; font-weight: 800; color: #15171a; line-height: 1.2; letter-spacing: -0.02em;\">
                                            ¡Hola, {$nombre}!
                                        </h1>

                                        <p style=\"margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #464b4f; font-weight: 400;\">
                                            Ingresá el siguiente código de 6 dígitos en la pantalla de verificación para activar tu cuenta de usuario:
                                        </p>

                                        <!-- Caja Oficial Nokia 1100 con Código OTP -->
                                        <div style=\"background-color: #111315; border: 2px solid #c6e24a; border-radius: 4px; padding: 22px 16px; text-align: center; margin: 20px 0;\">
                                            <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: 0.16em; color: #9ba19f; text-transform: uppercase; display: block; margin-bottom: 8px;\">
                                                CÓDIGO DE SEGURIDAD (OTP)
                                            </span>
                                            <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 36px; font-weight: 700; letter-spacing: 0.22em; color: #c6e24a; display: inline-block;\">
                                                {$codeFormatted}
                                            </span>
                                        </div>

                                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #e5e1d6; border: 1px solid #d3cebf; border-radius: 2px; margin-bottom: 20px;\">
                                            <tr>
                                                <td style=\"padding: 12px 16px; font-family: 'IBM Plex Sans', Arial, sans-serif; font-size: 12px; color: #5c6165; line-height: 1.5;\">
                                                    <strong style=\"color: #15171a;\">Validez:</strong> Este código expira en <strong>15 minutos</strong>.<br>
                                                    Si no solicitaste esta cuenta, podés ignorar este correo de forma segura.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 18px 28px; background-color: #111315; border-top: 1px solid #222729; text-align: center;\">
                                        <p style=\"margin: 0; font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #9ba19f; letter-spacing: 0.05em; text-transform: uppercase;\">
                                            &copy; {$year} Nokia 1100 · Sistema de Gestión y Stock
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar email OTP: {$mail->ErrorInfo}");
            $_SESSION['mail_debug_notice'] = 'Error de PHPMailer: ' . $mail->ErrorInfo;
            return false;
        }
    }

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/index.php");
        }

        $fail = function(string $msg, ?string $unverifiedEmail = null) {
            if ($this->is_ajax()) {
                header('Content-Type: application/json; charset=utf-8');
                $payload = ['success' => false, 'message' => $msg];
                if ($unverifiedEmail !== null) {
                    $payload['unverified'] = true;
                    $payload['email'] = $unverifiedEmail;
                }
                echo json_encode($payload);
                exit();
            }
            if ($unverifiedEmail !== null) {
                $this->redirect(BASE_URL . "/index.php?action=verify_otp_modal&email=" . urlencode($unverifiedEmail) . "&error=" . urlencode($msg));
            }
            $this->redirect(BASE_URL . "/index.php?error=" . urlencode($msg));
        };

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $fail("Token de seguridad inválido o sesión expirada.");
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $fail("Por favor complete todos los campos");
        }

        $user = $this->auth_model->find_by_username($username);

        require_once __DIR__ . '/../../../config/audit.php';

        if (!$user) {
            audit_log($this->conn, 'LOGIN_FAIL', null, 'usuario', null, "Usuario no encontrado: $username", $username);
            $fail("Usuario o contraseña incorrectos");
        }

        if (!password_verify($password, $user['contrasena'])) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Contraseña incorrecta para: $username", $username);
            $fail("Usuario o contraseña incorrectos");
        }

        if ((int) $user['is_active'] === 0) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Intento de login en cuenta desactivada: $username", $username);
            $fail("Esta cuenta fue desactivada. Contacte al administrador.");
        }

        if ((int) $user['verificado'] === 0) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Login bloqueado: email sin verificar para $username", $username);
            $fail("Tu cuenta aún no fue verificada. Ingresá el código de 6 dígitos que enviamos a tu correo.", $user['email'] ?? '');
        }

        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['username'] = $user['nombre_usuario'];
        $_SESSION['role'] = $user['rol'];

        $session_token = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $session_token;
        $this->auth_model->update_session_token($user['id_usuario'], $session_token);

        audit_log($this->conn, 'LOGIN_OK', $user['id_usuario'], 'usuario', $user['id_usuario'], "Sesión iniciada correctamente");

        $welcome_msg = urlencode("Bienvenido, " . $user['nombre_usuario']);
        $redirectUrl = ($user['rol'] === 'admin')
            ? BASE_URL . "/modules/admin/index.php?success=" . $welcome_msg
            : BASE_URL . "/modules/employee/index.php?success=" . $welcome_msg;

        if ($this->is_ajax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'redirect' => $redirectUrl,
                'message' => "Bienvenido, " . $user['nombre_usuario']
            ]);
            exit();
        }

        $this->redirect($redirectUrl);
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        $this->redirect(BASE_URL . "/index.php");
    }

    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/index.php?action=register");
        }

        $fail = function(string $msg) {
            if ($this->is_ajax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $this->redirect(BASE_URL . "/index.php?action=register&error=" . urlencode($msg));
        };

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $fail("Token de seguridad inválido o expirado.");
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dni = trim($_POST['dni'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($nombre) || empty($apellido) || empty($dni) || empty($username) || empty($password) || empty($password_confirm)) {
            $fail("Completá todos los campos requeridos");
        }

        if ($password !== $password_confirm) {
            $fail("Las contraseñas no coinciden");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail("El formato del email no es válido");
        }

        $pass_check = $this->validate_password($password);
        if ($pass_check !== true) {
            $fail($pass_check);
        }

        if ($this->auth_model->username_exists($username)) {
            $fail("El nombre de usuario ya está en uso");
        }

        if ($this->auth_model->dni_exists($dni)) {
            $fail("El DNI ingresado ya está registrado");
        }

        if ($this->auth_model->email_exists($email)) {
            $fail("El email ingresado ya está registrado");
        }

        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Generar código OTP numérico de 6 dígitos con validez de 15 minutos
            $otpCode = sprintf('%06d', random_int(100000, 999999));
            $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $persona_data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'dni' => $dni,
                'telefono' => $telefono,
                'email' => $email,
                'direccion' => $direccion
            ];
            $user_data = [
                'username' => $username,
                'hashed_password' => $hashed,
                'email' => $email,
                'token_verificacion' => $otpCode,
                'token_expira' => $expira
            ];

            $res = $this->auth_model->create_user_transaction($persona_data, $user_data);

            require_once __DIR__ . '/../../../config/audit.php';
            $id_ejecutor = $_SESSION['user_id'] ?? null;
            audit_log($this->conn, 'USER_CREATE', $id_ejecutor, 'usuario', $res['id_usuario'], "Nuevo usuario creado: " . $res['username'] . " (" . $res['nombre'] . " " . $res['apellido'] . ")");

            $mailSent = false;

            // Solo se envía correo de verificación si NO es el primer usuario (que se asigna como admin verificado por defecto)
            if ($res['rol'] !== 'admin') {
                $mailSent = $this->send_otp_email($email, $nombre, $otpCode);
            } else {
                $mailSent = true;
            }

            if ($this->is_ajax()) {
                header('Content-Type: application/json; charset=utf-8');
                if ($res['rol'] === 'admin') {
                    echo json_encode([
                        'success' => true,
                        'is_admin' => true,
                        'message' => 'Registro exitoso del administrador inicial. Ya podés ingresar.'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'require_otp' => true,
                        'email' => $email,
                        'mail_sent' => $mailSent,
                        'message' => $mailSent
                            ? 'Registro exitoso. Te enviamos un código de 6 dígitos a tu correo.'
                            : 'Registro exitoso, pero ocurrió un problema al enviar el correo con el código.'
                    ]);
                }
                exit();
            }

            if ($mailSent && $res['rol'] !== 'admin') {
                $this->redirect(BASE_URL . "/index.php?action=verify_otp_modal&email=" . urlencode($email) . "&success=" . urlencode("Registro exitoso. Ingresá el código de 6 dígitos que enviamos a tu email."));
            } elseif ($res['rol'] === 'admin') {
                $this->redirect(BASE_URL . "/index.php?success=" . urlencode("Registro exitoso del administrador inicial. Ya podés ingresar."));
            } else {
                $debug = isset($_SESSION['mail_debug_notice']) ? " (" . $_SESSION['mail_debug_notice'] . ")" : "";
                $this->redirect(BASE_URL . "/index.php?action=verify_otp_modal&email=" . urlencode($email) . "&error=" . urlencode("Registro creado, pero no se pudo enviar el correo de verificación" . $debug . "."));
            }

        } catch (Exception $e) {
            $fail($e->getMessage());
        }
    }

    /**
     * Endpoint AJAX para verificar el código OTP de 6 dígitos (Bencho OneTimeCode)
     */
    public function verify_otp()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o sesión expirada.']);
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if (empty($email) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Debe ingresar el correo y el código.']);
            exit();
        }

        if (!preg_match('/^[0-9]{6}$/', $code)) {
            echo json_encode(['success' => false, 'message' => 'El código debe tener exactamente 6 dígitos.']);
            exit();
        }

        $result = $this->auth_model->verify_otp($email, $code);
        if (!empty($result['success'])) {
            require_once __DIR__ . '/../../../config/audit.php';
            $userId = $result['id_usuario'] ?? null;
            $username = $result['username'] ?? $email;
            audit_log($this->conn, 'EMAIL_VERIFIED', $userId, 'usuario', $userId, "Cuenta verificada con código OTP de 6 dígitos: $username");
        }

        echo json_encode($result);
        exit();
    }

    /**
     * Endpoint AJAX para reenviar un nuevo código OTP de 6 dígitos
     */
    public function resend_otp()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o sesión expirada.']);
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email no especificado.']);
            exit();
        }

        $user = $this->auth_model->find_unverified_by_email_or_user($email);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No se encontró ninguna cuenta asociada a este correo.']);
            exit();
        }

        if ((int)$user['verificado'] === 1) {
            echo json_encode(['success' => true, 'already_verified' => true, 'message' => 'La cuenta ya está verificada. Podés iniciar sesión.']);
            exit();
        }

        // Generar nuevo código numérico de 6 dígitos con validez de 15 minutos
        $newOtp = sprintf('%06d', random_int(100000, 999999));
        $newExpira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $updated = $this->auth_model->update_otp($user['id_usuario'], $newOtp, $newExpira);
        if (!$updated) {
            echo json_encode(['success' => false, 'message' => 'Error de base de datos al regenerar el código.']);
            exit();
        }

        $nombre = !empty($user['nombre']) ? $user['nombre'] : $user['nombre_usuario'];
        $emailTarget = !empty($user['email']) ? $user['email'] : $email;
        $mailSent = $this->send_otp_email($emailTarget, $nombre, $newOtp);

        if ($mailSent) {
            echo json_encode(['success' => true, 'message' => 'Nuevo código de 6 dígitos enviado a tu correo. Válido por 15 minutos.']);
        } else {
            $debug = isset($_SESSION['mail_debug_notice']) ? " (" . $_SESSION['mail_debug_notice'] . ")" : "";
            echo json_encode(['success' => false, 'message' => 'No se pudo enviar el correo' . $debug . '.']);
        }
        exit();
    }

    public function forgot_password()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/auth/forgot_password.php");
        }

        // Validación Anti-CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $this->redirect(BASE_URL . "/auth/forgot_password.php?error=" . urlencode("Token de seguridad inválido o expirado."));
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $this->redirect(BASE_URL . "/auth/forgot_password.php?error=Por favor ingrese su correo electrónico");
        }

        $user = $this->auth_model->find_by_email($email);

        if ($user) {
            $id_usuario = $user['id_usuario'];
            $nombre = $user['nombre'];

            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->auth_model->set_reset_token($id_usuario, $token, $expira);

            $resetLink = $this->app_url() . "/modules/auth/index.php?action=reset_password_view&token=" . $token;

            require_once __DIR__ . '/../../../config/config_mail.php';

            $smtp = $this->smtp_settings();
            $smtpUser = $smtp['user'];
            $smtpPass = $smtp['pass'];
            $mailSent = false;

            if (empty($smtpUser) || empty($smtpPass)) {
                error_log('SMTP_USER/SMTP_PASS no configurados. Define variables de entorno para enviar correos.');
                $_SESSION['mail_debug_notice'] = 'No se pudo enviar el correo: faltan credenciales SMTP.';
            } elseif (cargarPHPMailer()) {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = $smtp['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUser;
                    $mail->Password = $smtpPass;
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $smtp['port'];
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
                    $mail->addAddress($email, $nombre);
                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de contraseña - Nokia 1100';
                    $year = date('Y');

                    $mail->Body = "
                    <!DOCTYPE html>
                    <html lang=\"es\">
                    <head>
                        <meta charset=\"utf-8\">
                        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                        <title>Recuperación de Contraseña - Nokia 1100</title>
                        <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
                        <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
                        <link href=\"https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;700;800&family=IBM+Plex+Mono:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap\" rel=\"stylesheet\">
                    </head>
                    <body style=\"margin: 0; padding: 0; background-color: #eeebe3; font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #15171a;\">
                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #eeebe3; padding: 40px 16px;\">
                            <tr>
                                <td align=\"center\">
                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 480px; background-color: #f8f6f0; border: 1px solid #b5af9c; border-radius: 4px; overflow: hidden; box-shadow: 0 12px 32px -6px rgba(21, 23, 26, 0.08);\">
                                        <!-- Barra Superior Grafito Oficial Nokia 1100 -->
                                        <tr>
                                            <td style=\"background-color: #111315; padding: 16px 24px; border-bottom: 2px solid #c6e24a;\">
                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                                    <tr>
                                                        <td align=\"left\" style=\"vertical-align: middle;\">
                                                            <span style=\"font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; color: #c6e24a; text-transform: uppercase;\">
                                                                SISTEMA NOKIA
                                                            </span>
                                                            <span style=\"display: inline-block; margin-left: 6px; background-color: #c6e24a; color: #15171a; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 2px; vertical-align: middle;\">
                                                                1100
                                                            </span>
                                                        </td>
                                                        <td align=\"right\" style=\"vertical-align: middle;\">
                                                            <span style=\"display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #c6e24a;\"></span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <!-- Cuerpo del Mensaje -->
                                        <tr>
                                            <td style=\"padding: 36px 32px 30px; text-align: left;\">
                                                <!-- Badge de módulo -->
                                                <div style=\"margin-bottom: 14px;\">
                                                    <span style=\"display: inline-block; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; color: #5c6165; text-transform: uppercase; border: 1px solid #d3cebf; padding: 3px 8px; border-radius: 2px; background-color: #eeebe3;\">
                                                        RECUPERACIÓN DE ACCESO
                                                    </span>
                                                </div>

                                                <h1 style=\"margin: 0 0 14px 0; font-family: 'Bricolage Grotesque', 'IBM Plex Sans', Arial, sans-serif; font-size: 24px; font-weight: 800; color: #15171a; line-height: 1.2; letter-spacing: -0.02em;\">
                                                    Hola, {$nombre}
                                                </h1>

                                                <p style=\"margin: 0 0 24px 0; font-size: 14px; line-height: 1.65; color: #464b4f; font-weight: 400;\">
                                                    Recibimos una solicitud para restablecer la contraseña de tu cuenta en el sistema Nokia 1100. Hacé clic en el siguiente botón para crear una nueva clave segura:
                                                </p>

                                                <!-- Botón de acción Nokia 1100 -->
                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"margin: 0 0 26px 0;\">
                                                    <tr>
                                                        <td align=\"center\">
                                                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
                                                                <tr>
                                                                    <td align=\"center\" style=\"border-radius: 2px; background-color: #15171a; border: 1px solid #15171a; box-shadow: 3px 3px 0 #c6e24a;\">
                                                                        <a href=\"{$resetLink}\" target=\"_blank\" style=\"display: inline-block; padding: 13px 34px; font-family: 'IBM Plex Sans', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 2px; letter-spacing: 0.02em;\">
                                                                            Restablecer contraseña &rarr;
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- Caja informativa con validez -->
                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #e5e1d6; border: 1px solid #d3cebf; border-radius: 2px; margin-bottom: 20px;\">
                                                    <tr>
                                                        <td style=\"padding: 12px 16px; font-family: 'IBM Plex Sans', Arial, sans-serif; font-size: 12px; color: #5c6165; line-height: 1.5;\">
                                                            <strong style=\"color: #15171a;\">Seguridad:</strong> Por motivos de seguridad, este enlace vencerá en <strong>60 minutos</strong> desde su solicitud.<br>
                                                            Si no solicitaste este cambio, podés ignorar este correo de forma segura.
                                                        </td>
                                                    </tr>
                                                </table>

                                                <p style=\"margin: 0; font-size: 11px; line-height: 1.5; color: #757c82;\">
                                                    Si el botón no abre el enlace, copiá y pegá la siguiente URL en tu navegador:<br>
                                                    <a href=\"{$resetLink}\" target=\"_blank\" style=\"color: #1f4e8c; word-break: break-all; text-decoration: underline;\">{$resetLink}</a>
                                                </p>
                                            </td>
                                        </tr>
                                        <!-- Footer -->
                                        <tr>
                                            <td style=\"padding: 18px 28px; background-color: #111315; border-top: 1px solid #222729; text-align: center;\">
                                                <p style=\"margin: 0; font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #9ba19f; letter-spacing: 0.05em; text-transform: uppercase;\">
                                                    &copy; {$year} Nokia 1100 · Sistema de Gestión y Stock
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>
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

        $this->redirect(BASE_URL . "/auth/forgot_password.php?success=Si el correo existe en nuestra base de datos, hemos enviado un enlace de recuperación.");
    }

    public function reset_password_view()
    {
        $token = $_GET['token'] ?? '';
        $isValidToken = false;
        $id_usuario = 0;

        if (!empty($token)) {
            $user = $this->auth_model->find_by_valid_token($token);
            if ($user) {
                $isValidToken = true;
                $id_usuario = $user['id_usuario'];
            }
        }

        $this->render_view(__DIR__ . '/../Views/reset.php', [
            'token' => $token,
            'isValidToken' => $isValidToken,
            'id_usuario' => $id_usuario
        ]);
    }

    public function reset_password()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/index.php");
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($password_confirm)) {
            $this->redirect(BASE_URL . "/modules/auth/index.php?action=reset_password_view&token=" . urlencode($token) . "&error=Por favor complete todos los campos");
        }

        if ($password !== $password_confirm) {
            $this->redirect(BASE_URL . "/modules/auth/index.php?action=reset_password_view&token=" . urlencode($token) . "&error=Las contraseñas no coinciden");
        }

        $pass_check = $this->validate_password($password);
        if ($pass_check !== true) {
            $this->redirect(BASE_URL . "/modules/auth/index.php?action=reset_password_view&token=" . urlencode($token) . "&error=" . urlencode($pass_check));
        }

        $user = $this->auth_model->find_by_valid_token($token);

        if (!$user) {
            $this->redirect(BASE_URL . "/modules/auth/index.php?action=reset_password_view&token=" . urlencode($token) . "&error=Token inválido o expirado");
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if ($this->auth_model->update_password($user['id_usuario'], $hashed)) {
            $this->redirect(BASE_URL . "/index.php?success=Contraseña actualizada exitosamente. Ya puedes iniciar sesión.");
        } else {
            $this->redirect(BASE_URL . "/modules/auth/index.php?action=reset_password_view&token=" . urlencode($token) . "&error=Ocurrió un error al actualizar la contraseña");
        }
    }



    public function guestLogin()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/index.php");
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $this->redirect(BASE_URL . "/index.php?error=Token de seguridad inválido o expirado.");
        }

        $guest_name = trim($_POST['guest_name'] ?? '');

        if (empty($guest_name)) {
            $this->redirect(BASE_URL . "/index.php?error=Por favor ingrese su nombre de invitado");
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = 'guest';
        $_SESSION['username'] = $guest_name;
        $_SESSION['role'] = 'guest';
        $_SESSION['session_token'] = bin2hex(random_bytes(32));

        audit_log($this->conn, 'LOGIN_GUEST', null, 'usuario', null, "Sesión de invitado iniciada: " . $guest_name);

        $welcome_msg = urlencode("Bienvenido al sistema, " . $guest_name);
        $this->redirect(BASE_URL . "/modules/employee/index.php?success=" . $welcome_msg);
    }
}

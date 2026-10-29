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

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->redirect(BASE_URL . "/index.php");
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $this->redirect(BASE_URL . "/index.php?error=Token de seguridad inválido o sesión expirada.");
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->redirect(BASE_URL . "/index.php?error=Por favor complete todos los campos");
        }

        $user = $this->auth_model->find_by_username($username);

        require_once __DIR__ . '/../../../config/audit.php';

        if (!$user) {
            audit_log($this->conn, 'LOGIN_FAIL', null, 'usuario', null, "Usuario no encontrado: $username", $username);
            $this->redirect(BASE_URL . "/index.php?error=Usuario o contraseña incorrectos");
        }

        if (!password_verify($password, $user['contrasena'])) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Contraseña incorrecta para: $username", $username);
            $this->redirect(BASE_URL . "/index.php?error=Usuario o contraseña incorrectos");
        }

        if ((int) $user['is_active'] === 0) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Intento de login en cuenta desactivada: $username", $username);
            $this->redirect(BASE_URL . "/index.php?error=Esta cuenta fue desactivada. Contacte al administrador.");
        }

        if ((int) $user['verificado'] === 0) {
            audit_log($this->conn, 'LOGIN_FAIL', $user['id_usuario'], 'usuario', $user['id_usuario'], "Login bloqueado: email sin verificar para $username", $username);
            $this->redirect(BASE_URL . "/index.php?error=Tu cuenta aún no fue verificada. Revisá tu email.");
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
        if ($user['rol'] === 'admin') {
            $this->redirect(BASE_URL . "/modules/admin/index.php?success=" . $welcome_msg);
        } else {
            $this->redirect(BASE_URL . "/modules/employee/index.php?success=" . $welcome_msg);
        }
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

        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['session_token']) || !hash_equals($_SESSION['session_token'], (string)$token)) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=Token de seguridad inválido o expirado.");
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
            $this->redirect(BASE_URL . "/index.php?action=register&error=Completá todos los campos requeridos");
        }

        if ($password !== $password_confirm) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=Las contraseñas no coinciden");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=El formato del email no es válido");
        }

        $pass_check = $this->validate_password($password);
        if ($pass_check !== true) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=" . urlencode($pass_check));
        }

        if ($this->auth_model->username_exists($username)) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=El nombre de usuario ya está en uso");
        }

        if ($this->auth_model->dni_exists($dni)) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=El DNI ingresado ya está registrado");
        }

        if ($this->auth_model->email_exists($email)) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=El email ingresado ya está registrado");
        }

        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Generar token de verificación de correo
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

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
                'token_verificacion' => $token,
                'token_expira' => $expira
            ];

            $res = $this->auth_model->create_user_transaction($persona_data, $user_data);

            require_once __DIR__ . '/../../../config/audit.php';
            $id_ejecutor = $_SESSION['user_id'] ?? null;
            audit_log($this->conn, 'USER_CREATE', $id_ejecutor, 'usuario', $res['id_usuario'], "Nuevo usuario creado: " . $res['username'] . " (" . $res['nombre'] . " " . $res['apellido'] . ")");

            $mailSent = false;

            // Solo se envía correo de verificación si NO es el primer usuario (que se asigna como admin verificado por defecto)
            if ($res['rol'] !== 'admin') {
                $verifyLink = $this->app_url() . "/auth/verificar.php?token=" . $token;

                require_once __DIR__ . '/../../../config/config_mail.php';

                $smtp = $this->smtp_settings();
                $smtpUser = $smtp['user'];
                $smtpPass = $smtp['pass'];

                if (empty($smtpUser) || empty($smtpPass)) {
                    error_log('SMTP_USER/SMTP_PASS no configurados para enviar correos de registro.');
                    $_SESSION['mail_debug_notice'] = 'Credenciales SMTP vacías en config_mail.php / mail.local.php';
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
                        $mail->Subject = 'Verificá tu Cuenta - Nokia 1100';

                        $year = date('Y');

                        // Diseño de Plantilla de Email
                        $mail->Body = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset=\"utf-8\">
                            <title>Verificá tu Cuenta - Nokia 1100 System</title>
                        </head>
                        <body style=\"margin: 0; padding: 0; background-color: #181E26; font-family: 'Inter', Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;\">
                            <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #181E26; padding: 40px 20px;\">
                                <tr>
                                    <td align=\"center\">
                                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 500px; background-color: #1F2937; border: 1px solid #374151; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);\">
                                            <!-- Línea de acento gradiente superior -->
                                            <tr>
                                                <td height=\"4\" style=\"background: linear-gradient(90deg, #0EA5A0, #5EEAD4);\"></td>
                                            </tr>
                                            <!-- Cuerpo del Mensaje -->
                                            <tr>
                                                <td style=\"padding: 40px 30px; text-align: center;\">
                                                    <!-- Logo -->
                                                    <h1 style=\"margin: 0 0 5px 0; font-family: 'Outfit', Arial, sans-serif; font-size: 26px; font-weight: 800; color: #FAFAFA; letter-spacing: -0.5px;\">
                                                        NOKIA<span style=\"color: #0EA5A0;\">1100</span>
                                                    </h1>
                                                    <p style=\"margin: 0 0 35px 0; font-size: 10px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 2px;\">
                                                        Sistema de Gestión
                                                    </p>
                                                    
                                                    <!-- Texto principal -->
                                                    <h2 style=\"margin: 0 0 15px 0; font-family: 'Outfit', Arial, sans-serif; font-size: 20px; font-weight: 600; color: #FAFAFA; letter-spacing: -0.3px;\">
                                                        ¡Hola, {$nombre}!
                                                    </h2>
                                                    <p style=\"margin: 0 0 35px 0; font-size: 14px; line-height: 1.6; color: #9CA3AF; font-weight: 400;\">
                                                        Gracias por registrarte en el sistema Nokia 1100. Para poder activar tu cuenta y empezar a operar, necesitamos que verifiques tu dirección de correo electrónico haciendo clic en el botón de abajo.
                                                    </p>
                                                    
                                                    <!-- Botón de acción -->
                                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin: 0 auto 35px auto;\">
                                                        <tr>
                                                            <td align=\"center\" style=\"border-radius: 30px; background-color: #0EA5A0;\">
                                                                <a href=\"{$verifyLink}\" target=\"_blank\" style=\"display: inline-block; padding: 14px 36px; font-size: 14px; font-weight: 700; color: #FFFFFF; text-decoration: none; border-radius: 30px;\">
                                                                    Verificar mi Cuenta
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    
                                                    <!-- Explicación de vencimiento -->
                                                    <p style=\"margin: 0; font-size: 11px; color: #6B7280; line-height: 1.5;\">
                                                        Este enlace vencerá en 24 horas.<br>
                                                        Si no creaste esta cuenta, podés ignorar este correo de forma segura.
                                                    </p>
                                                </td>
                                            </tr>
                                            <!-- Footer -->
                                            <tr>
                                                <td style=\"padding: 20px 30px; background-color: #181E26; border-top: 1px solid #374151; text-align: center;\">
                                                    <p style=\"margin: 0; font-size: 10px; color: #6B7280;\">
                                                        &copy; {$year} Nokia 1100 System. Todos los derechos reservados.
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
                        $mailSent = true;
                    } catch (Exception $e) {
                        error_log("Error al enviar email de verificación: {$mail->ErrorInfo}");
                        $_SESSION['mail_debug_notice'] = 'Error de PHPMailer: ' . $mail->ErrorInfo;
                    }
                }
            } else {
                $mailSent = true; // El admin inicial no requiere envío de verificación por correo
            }

            if ($mailSent && $res['rol'] !== 'admin') {
                $this->redirect(BASE_URL . "/index.php?success=" . urlencode("Registro exitoso. Te enviamos un email de verificación a tu casilla."));
            } elseif ($res['rol'] === 'admin') {
                $this->redirect(BASE_URL . "/index.php?success=" . urlencode("Registro exitoso del administrador inicial. Ya podés ingresar."));
            } else {
                $debug = isset($_SESSION['mail_debug_notice']) ? " (" . $_SESSION['mail_debug_notice'] . ")" : "";
                $this->redirect(BASE_URL . "/index.php?success=" . urlencode("Registro exitoso, pero no se pudo enviar el correo de verificación" . $debug . "."));
            }

        } catch (Exception $e) {
            $this->redirect(BASE_URL . "/index.php?action=register&error=" . urlencode($e->getMessage()));
        }
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
                    <html>
                    <head>
                        <meta charset=\"utf-8\">
                        <title>Recuperación de Contraseña - Nokia 1100 System</title>
                    </head>
                    <body style=\"margin: 0; padding: 0; background-color: #181E26; font-family: 'Inter', Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;\">
                        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #181E26; padding: 40px 20px;\">
                            <tr>
                                <td align=\"center\">
                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 500px; background-color: #1F2937; border: 1px solid #374151; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5);\">
                                        <!-- Línea de acento gradiente superior -->
                                        <tr>
                                            <td height=\"4\" style=\"background: linear-gradient(90deg, #0EA5A0, #5EEAD4);\"></td>
                                        </tr>
                                        <!-- Cuerpo del Mensaje -->
                                        <tr>
                                            <td style=\"padding: 40px 30px; text-align: center;\">
                                                <!-- Logo -->
                                                <h1 style=\"margin: 0 0 5px 0; font-family: 'Outfit', Arial, sans-serif; font-size: 26px; font-weight: 800; color: #FAFAFA; letter-spacing: -0.5px;\">
                                                    NOKIA<span style=\"color: #0EA5A0;\">1100</span>
                                                </h1>
                                                <p style=\"margin: 0 0 35px 0; font-size: 10px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 2px;\">
                                                    Sistema de Gestión
                                                </p>
                                                
                                                <!-- Texto principal -->
                                                <h2 style=\"margin: 0 0 15px 0; font-family: 'Outfit', Arial, sans-serif; font-size: 20px; font-weight: 600; color: #FAFAFA; letter-spacing: -0.3px;\">
                                                    Hola, {$nombre}
                                                </h2>
                                                <p style=\"margin: 0 0 35px 0; font-size: 14px; line-height: 1.6; color: #9CA3AF; font-weight: 400;\">
                                                    Recibimos una solicitud para restablecer la contraseña de tu cuenta. Hacé clic en el siguiente botón para crear una nueva contraseña segura.
                                                </p>
                                                
                                                <!-- Botón de acción -->
                                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin: 0 auto 35px auto;\">
                                                    <tr>
                                                        <td align=\"center\" style=\"border-radius: 30px; background-color: #0EA5A0;\">
                                                            <a href=\"{$resetLink}\" target=\"_blank\" style=\"display: inline-block; padding: 14px 36px; font-size: 14px; font-weight: 700; color: #FFFFFF; text-decoration: none; border-radius: 30px;\">
                                                                Restablecer Contraseña
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </table>
                                                
                                                <!-- Explicación de vencimiento -->
                                                <p style=\"margin: 0; font-size: 11px; color: #6B7280; line-height: 1.5;\">
                                                    Este enlace vencerá en 1 hora.<br>
                                                    Si no solicitaste este cambio, podés ignorar este correo de forma segura.
                                                </p>
                                            </td>
                                        </tr>
                                        <!-- Footer -->
                                        <tr>
                                            <td style=\"padding: 20px 30px; background-color: #181E26; border-top: 1px solid #374151; text-align: center;\">
                                                <p style=\"margin: 0; font-size: 10px; color: #6B7280;\">
                                                    &copy; {$year} Nokia 1100 System. Todos los derechos reservados.
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

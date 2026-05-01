<?php
// config_mail.php
// Configuración centralizada de correo.
// Prioriza variables de entorno para no exponer credenciales en código.
$local_mail_config = __DIR__ . '/mail.local.php';
if (file_exists($local_mail_config)) {
    require_once $local_mail_config;
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', getenv('SMTP_USER') ?: '');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Nokia 1100 System');
}

// Función para cargar PHPMailer (Composer o instalación manual)
function cargarPHPMailer() {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return true;
    }

    $composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($composer_autoload)) {
        require_once $composer_autoload;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }
    }

    $manual_base_dir = dirname(__DIR__) . '/PHPMailer-master/src/';
    if (file_exists($manual_base_dir . 'Exception.php')) {
        require_once $manual_base_dir . 'Exception.php';
        require_once $manual_base_dir . 'PHPMailer.php';
        require_once $manual_base_dir . 'SMTP.php';
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    return false;
}
?>

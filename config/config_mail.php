<?php
// config_mail.php
// Configuración centralizada de correo
// CAMBIAR ESTOS VALORES POR LOS TUYOS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'fursantic@gmail.com'); // PON TU CORREO REAL
define('SMTP_PASS', 't jesncpvmuuuwzzn'); // PON TU CONTRASEÑA DE APLICACIÓN (NO LA DE GMAIL NORMAL)
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'no-reply@nokia1100.com');
define('SMTP_FROM_NAME', 'Nokia 1100 System');

// Función para cargar PHPMailer sin Composer (si se descargó zip)
function cargarPHPMailer() {
    // Ajustar si la carpeta se llama diferente, ej: PHPMailer-master
    $base_dir = dirname(__DIR__) . '/PHPMailer-master/src/';
    
    if (file_exists($base_dir . 'Exception.php')) {
        require_once $base_dir . 'Exception.php';
        require_once $base_dir . 'PHPMailer.php';
        require_once $base_dir . 'SMTP.php';
        return true;
    }
    return false;
}
?>

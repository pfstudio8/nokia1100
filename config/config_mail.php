<?php
// config_mail.php
// Configuración centralizada de correo.
// Prioriza variables de entorno para no exponer credenciales en código.
require_once __DIR__ . '/bootstrap.php';
$validator = Dotenv\Dotenv::createImmutable(dirname(__DIR__));

$validator->required([
    'SMTP_HOST', 'SMTP_USER', 'SMTP_PASS',
    'SMTP_PORT', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'
])->notEmpty();
$validator->required('SMTP_PORT')->isInteger();

$port = (int) $_ENV['SMTP_PORT'];
if ($port < 1 || $port > 65535) {
    throw new RuntimeException('SMTP_PORT fuera de rango');
}
if (!filter_var($_ENV['SMTP_FROM_EMAIL'], FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('SMTP_FROM_EMAIL no válido');
}

foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS',
          'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'] as $name) {
    if (!defined($name)) define($name, $_ENV[$name]);
}
if (!defined('SMTP_PORT')) define('SMTP_PORT', $port);


// Función para verificar si PHPMailer está cargado vía Composer
function cargarPHPMailer() {
    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}
?>

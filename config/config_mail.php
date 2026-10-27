<?php
// config_mail.php
// Configuración centralizada de correo.
// Prioriza variables de entorno para no exponer credenciales en código.
require_once __DIR__ . '/bootstrap.php';
// Las variables SMTP son opcionales: si están vacías, los controladores detectan
// la falta de credenciales y continúan sin enviar correo (en vez de romper el registro).
$smtpDefaults = [
    'SMTP_HOST'       => 'smtp.gmail.com',
    'SMTP_PORT'       => '587',
    'SMTP_USER'       => '',
    'SMTP_PASS'       => '',
    'SMTP_FROM_EMAIL' => '',
    'SMTP_FROM_NAME'  => 'Nokia 1100 System',
];
foreach ($smtpDefaults as $name => $default) {
    $value = $_ENV[$name] ?? '';
    $_ENV[$name] = ($value === '' || $value === null) ? $default : $value;
}

$port = filter_var($_ENV['SMTP_PORT'], FILTER_VALIDATE_INT);
if ($port === false || $port < 1 || $port > 65535) {
    throw new RuntimeException('SMTP_PORT fuera de rango');
}
// Si no se configuró remitente, se usa el usuario SMTP
if ($_ENV['SMTP_FROM_EMAIL'] === '') {
    $_ENV['SMTP_FROM_EMAIL'] = $_ENV['SMTP_USER'];
}
if ($_ENV['SMTP_FROM_EMAIL'] !== '' && !filter_var($_ENV['SMTP_FROM_EMAIL'], FILTER_VALIDATE_EMAIL)) {
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

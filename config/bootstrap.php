<?php
date_default_timezone_set('America/Argentina/Buenos_Aires'); // GMT-3

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$dotenv->required([
    'DB_HOST', 'DB_USER', 'DB_NAME', 'BASE_URL'
])->notEmpty();
$dotenv->required('DB_PASS');

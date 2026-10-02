<?php
date_default_timezone_set('America/Argentina/Buenos_Aires'); // GMT-3

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$dotenv->required([
    'DB_HOST', 'DB_USER', 'DB_NAME', 'BASE_URL'
])->notEmpty();
$dotenv->required('DB_PASS');

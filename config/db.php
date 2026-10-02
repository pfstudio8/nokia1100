<?php
require_once __DIR__ . '/bootstrap.php';

define('BASE_URL', $_ENV['BASE_URL']);
require_once __DIR__ . '/../classes/Database.php';

// Instancia la clase Database (POO)
$db = new Database();

// Expone la variable $conn heredada para compatibilidad
$conn = $db->getConnection();


?>

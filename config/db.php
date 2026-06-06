<?php
define('BASE_URL', '/www/nokia1100');
require_once __DIR__ . '/../classes/Database.php';

// Instantiate the new OOP Database class
$db = new Database();

// Expose legacy $conn variable to prevent breaking existing scripts
$conn = $db->getConnection();

// Auto-migration: Check and add modulos_permitidos column if it does not exist
$res_permiso = $conn->query("SHOW COLUMNS FROM usuario LIKE 'modulos_permitidos'");
if ($res_permiso && $res_permiso->num_rows === 0) {
    $conn->query("ALTER TABLE usuario ADD COLUMN modulos_permitidos TEXT DEFAULT NULL AFTER rol");
}
?>

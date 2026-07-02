<?php
define('BASE_URL', '/nokia1100');
require_once __DIR__ . '/../classes/Database.php';

// Instancia la clase Database (POO)
$db = new Database();

// Expone la variable $conn heredada para compatibilidad
$conn = $db->getConnection();

// Automigración: crea modulos_permitidos si no existe
$res_permiso = $conn->query("SHOW COLUMNS FROM usuario LIKE 'modulos_permitidos'");
if ($res_permiso && $res_permiso->num_rows === 0) {
    $conn->query("ALTER TABLE usuario ADD COLUMN modulos_permitidos TEXT DEFAULT NULL AFTER rol");
}
?>

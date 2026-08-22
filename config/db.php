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

// Automigración: crea session_token si no existe para evitar sesiones múltiples
$res_session = $conn->query("SHOW COLUMNS FROM usuario LIKE 'session_token'");
if ($res_session && $res_session->num_rows === 0) {
    $conn->query("ALTER TABLE usuario ADD COLUMN session_token VARCHAR(255) DEFAULT NULL AFTER modulos_permitidos");
}

// Automigración: crea estado en venta si no existe (rollback de ventas)
$res_estado = $conn->query("SHOW COLUMNS FROM venta LIKE 'estado'");
if ($res_estado && $res_estado->num_rows === 0) {
    $conn->query("ALTER TABLE venta ADD COLUMN estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada'");
}
?>

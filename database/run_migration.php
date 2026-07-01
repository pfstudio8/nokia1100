<?php
// database/run_migration.php
require_once __DIR__ . "/../config/db.php";

$sql = "SHOW COLUMNS FROM usuario LIKE 'modulos_permitidos'";
$result = $conn->query($sql);

if ($result && $result->num_rows === 0) {
    $alter = "ALTER TABLE usuario ADD COLUMN modulos_permitidos TEXT DEFAULT NULL AFTER rol";
    if ($conn->query($alter)) {
        echo "Column 'modulos_permitidos' added successfully to 'usuario' table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'modulos_permitidos' already exists in 'usuario' table.\n";
}
?>

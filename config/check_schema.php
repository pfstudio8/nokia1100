<?php
require_once 'bd.php';

$tables = ['producto', 'producto_detalle', 'venta', 'detalle_venta', 'usuario', 'inventario'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo " - Error: " . $conn->error . "\n";
    }
    echo "\n";
}
?>

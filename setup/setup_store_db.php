<?php
require_once '../config/bd.php';

try {
    // Add column `tipo_venta` to `venta` table
    $sql = "ALTER TABLE venta 
            ADD COLUMN tipo_venta ENUM('local', 'online') DEFAULT 'local' AFTER metodo_de_pago";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table venta updated successfully with tipo_venta column.";
    } else {
         if (strpos($conn->error, "Duplicate column name") !== false) {
             echo "Column tipo_venta already exists.";
        } else {
            echo "Error updating table: " . $conn->error;
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>

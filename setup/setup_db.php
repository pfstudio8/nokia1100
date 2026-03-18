<?php
require_once '../config/bd.php';

try {
    // Add columns to usuario table
    $sql = "ALTER TABLE usuario 
            ADD COLUMN email VARCHAR(255) UNIQUE AFTER nombre_usuario,
            ADD COLUMN verificado TINYINT(1) DEFAULT 0,
            ADD COLUMN token_verificacion VARCHAR(255),
            ADD COLUMN token_expira DATETIME";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table usuario updated successfully";
    } else {
        // Check if error is due to duplicate column
        if (strpos($conn->error, "Duplicate column name") !== false) {
             echo "Columns already exist in usuario table.";
        } else {
            echo "Error updating table: " . $conn->error;
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>

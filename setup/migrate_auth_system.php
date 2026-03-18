<?php
require_once dirname(__DIR__) . '/config/bd.php';

try {
    echo "Starting migration...\n";

    // 1. Create usuarios_tienda table
    $sql_tienda = "CREATE TABLE IF NOT EXISTS usuarios_tienda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        verificado TINYINT(1) DEFAULT 0,
        token_verificacion VARCHAR(255),
        token_expira DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql_tienda)) {
        echo "Table 'usuarios_tienda' created/verified.\n";
    }

    // 2. Create usuarios_admin table
    $sql_admin = "CREATE TABLE IF NOT EXISTS usuarios_admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_usuario VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql_admin)) {
        echo "Table 'usuarios_admin' created/verified.\n";
    }

    // 3. Migrate data from old 'usuario' table
    // Migration for Store (Customers)
    $stmt_tienda = $conn->prepare("INSERT IGNORE INTO usuarios_tienda (email, password, verificado, token_verificacion, token_expira) 
                                   SELECT email, contrasena, verificado, token_verificacion, token_expira FROM usuario WHERE rol = 'cliente'");
    if ($stmt_tienda->execute()) {
        echo "Customers migrated: " . $stmt_tienda->affected_rows . "\n";
    }

    // Migration for Admins/Employees
    $stmt_admin = $conn->prepare("INSERT IGNORE INTO usuarios_admin (nombre_usuario, email, password, rol) 
                                  SELECT nombre_usuario, email, contrasena, rol FROM usuario WHERE rol IN ('admin', 'empleado')");
    if ($stmt_admin->execute()) {
        echo "Admins/Employees migrated: " . $stmt_admin->affected_rows . "\n";
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>

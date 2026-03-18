<?php
require_once '../config/bd.php';

try {
    // 1. Update 'usuario' rol enum
    // Note: modifying ENUM can be tricky. We redefine the column.
    $sql_rol = "ALTER TABLE usuario MODIFY COLUMN rol ENUM('admin', 'empleado', 'cliente') NOT NULL";
    if ($conn->query($sql_rol) === TRUE) {
        echo "Updated 'usuario' rol column.\n";
    } else {
        echo "Error updating 'usuario': " . $conn->error . "\n";
    }

    // 2. Update 'venta' table
    // Check if columns exist before adding (simple approach: try add, ignore duplicate error)
    $conn->query("ALTER TABLE venta ADD COLUMN id_usuario INT AFTER id_venta");
    $conn->query("ALTER TABLE venta ADD COLUMN direccion_envio TEXT AFTER tipo_venta");
    $conn->query("ALTER TABLE venta ADD COLUMN estado ENUM('pendiente', 'pagado', 'enviado', 'entregado') DEFAULT 'pendiente' AFTER total");
    echo "Updated 'venta' table columns.\n";

    // 3. Update 'producto_detalle' table
    $conn->query("ALTER TABLE producto_detalle ADD COLUMN imagen_url VARCHAR(255) AFTER descripcion");
    $conn->query("ALTER TABLE producto_detalle ADD COLUMN categoria VARCHAR(50) AFTER tipo_repuesto");
    echo "Updated 'producto_detalle' table columns.\n";
    
    // 4. Update existing products with dummy images/categories if null
    $conn->query("UPDATE producto_detalle SET categoria = 'Celulares' WHERE categoria IS NULL AND (modelo LIKE '%Iphone%' OR modelo LIKE '%Samsung%' OR modelo LIKE '%Motorola%')");
    $conn->query("UPDATE producto_detalle SET categoria = 'Accesorios' WHERE categoria IS NULL");
    
    // Placeholder images (can be replaced by user later)
    // We will use a generic placeholder or specific ones based on brand
    $conn->query("UPDATE producto_detalle SET imagen_url = 'https://via.placeholder.com/300?text=Celular' WHERE imagen_url IS NULL");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

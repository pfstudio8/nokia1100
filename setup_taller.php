<?php
require_once __DIR__ . '/config/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS `reparacion` (
        `id_reparacion` int(11) NOT NULL AUTO_INCREMENT,
        `codigo_orden` varchar(20) NOT NULL UNIQUE,
        `cliente_nombre` varchar(150) NOT NULL,
        `cliente_telefono` varchar(20) DEFAULT NULL,
        `cliente_email` varchar(150) DEFAULT NULL,
        `equipo_marca` varchar(100) NOT NULL,
        `equipo_modelo` varchar(100) NOT NULL,
        `equipo_imei` varchar(50) DEFAULT NULL,
        `falla_declarada` text NOT NULL,
        `observaciones` text DEFAULT NULL,
        `estado` enum('Recibido', 'En diagnóstico', 'En reparación', 'Listo', 'Entregado', 'Cancelado') DEFAULT 'Recibido',
        `presupuesto` decimal(10,2) DEFAULT NULL,
        `costo_total` decimal(10,2) DEFAULT 0.00,
        `fecha_ingreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `fecha_entrega` datetime DEFAULT NULL,
        `id_usuario_recibe` int(11) DEFAULT NULL,
        PRIMARY KEY (`id_reparacion`),
        FOREIGN KEY (`id_usuario_recibe`) REFERENCES `usuario`(`id_usuario`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `reparacion_repuesto` (
        `id_reparacion_repuesto` int(11) NOT NULL AUTO_INCREMENT,
        `id_reparacion` int(11) NOT NULL,
        `id_producto` int(11) NOT NULL,
        `cantidad` int(11) NOT NULL DEFAULT 1,
        `precio_unitario` decimal(10,2) NOT NULL,
        `fecha_asignacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_reparacion_repuesto`),
        FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion`(`id_reparacion`) ON DELETE CASCADE,
        FOREIGN KEY (`id_producto`) REFERENCES `producto`(`id_producto`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `reparacion_imagen` (
        `id_imagen` int(11) NOT NULL AUTO_INCREMENT,
        `id_reparacion` int(11) NOT NULL,
        `ruta_archivo` varchar(255) NOT NULL,
        `tipo` enum('Ingreso', 'Reparado', 'Documento') DEFAULT 'Ingreso',
        `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_imagen`),
        FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion`(`id_reparacion`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `reparacion_historial` (
        `id_historial` int(11) NOT NULL AUTO_INCREMENT,
        `id_reparacion` int(11) NOT NULL,
        `estado_anterior` varchar(50) DEFAULT NULL,
        `estado_nuevo` varchar(50) NOT NULL,
        `nota` text DEFAULT NULL,
        `fecha_cambio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `id_usuario` int(11) DEFAULT NULL,
        PRIMARY KEY (`id_historial`),
        FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion`(`id_reparacion`) ON DELETE CASCADE,
        FOREIGN KEY (`id_usuario`) REFERENCES `usuario`(`id_usuario`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Query executed successfully.<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}
echo "Setup complete.";
?>

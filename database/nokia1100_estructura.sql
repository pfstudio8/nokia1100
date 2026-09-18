CREATE DATABASE IF NOT EXISTS `nokia1100`;
USE `nokia1100`;

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tiempo_entrega` varchar(100) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT 0.00,
  `autorizado_por` varchar(100) DEFAULT NULL,
  `estado` enum('Pendiente','Recibido','Cancelado') DEFAULT 'Pendiente',
  PRIMARY KEY (`id_compra`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `compra_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `detalle_compra` (
  `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_compra`),
  KEY `id_compra` (`id_compra`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_compra_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `detalle_compra_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `precio_copiado` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE,
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventario` (
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_producto`),
  CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `producto_compra_historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_detalle_compra` int(11) NOT NULL,
  `nombre_producto` varchar(150) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `id_detalle_compra` (`id_detalle_compra`),
  CONSTRAINT `producto_compra_historial_ibfk_1` FOREIGN KEY (`id_detalle_compra`) REFERENCES `detalle_compra` (`id_detalle_compra`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `producto_detalle` (
  `id_producto` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `tipo_repuesto` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `stock_minimo` int(11) DEFAULT 0,
  PRIMARY KEY (`id_producto`),
  CONSTRAINT `producto_detalle_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `domicilio` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `atencion` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','empleado') DEFAULT 'empleado',
  `email` varchar(255) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `token_verificacion` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `modulos_permitidos` TEXT DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `id_persona` (`id_persona`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_de_pago` varchar(50) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `fk_venta_usuario` (`id_usuario`),
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas del módulo Taller

CREATE TABLE IF NOT EXISTS eparacion (
  id_reparacion int NOT NULL AUTO_INCREMENT,
  codigo_orden varchar(20) NOT NULL,
  id_cliente int NOT NULL,
  equipo_marca varchar(50) NOT NULL,
  equipo_modelo varchar(50) NOT NULL,
  equipo_imei varchar(30) DEFAULT NULL,
  alla_declarada text,
  observaciones text,
  presupuesto decimal(10,2) DEFAULT 0,
  costo_total decimal(10,2) DEFAULT 0,
  estado varchar(30) NOT NULL DEFAULT 'Recibido',
  id_usuario_recibe int DEFAULT NULL,
  echa_ingreso timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  echa_entrega timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id_reparacion),
  KEY id_cliente (id_cliente)
);

CREATE TABLE IF NOT EXISTS eparacion_historial (
  id_historial int NOT NULL AUTO_INCREMENT,
  id_reparacion int NOT NULL,
  estado_anterior varchar(30) DEFAULT NULL,
  estado_nuevo varchar(30) NOT NULL,
  
ota text,
  id_usuario int DEFAULT NULL,
  echa_cambio timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  KEY id_reparacion (id_reparacion)
);

CREATE TABLE IF NOT EXISTS eparacion_repuesto (
  id_reparacion_repuesto int NOT NULL AUTO_INCREMENT,
  id_reparacion int NOT NULL,
  id_producto int NOT NULL,
  cantidad int NOT NULL DEFAULT 1,
  precio_unitario decimal(10,2) NOT NULL,
  PRIMARY KEY (id_reparacion_repuesto)
);

CREATE TABLE IF NOT EXISTS eparacion_imagen (
  id_imagen int NOT NULL AUTO_INCREMENT,
  id_reparacion int NOT NULL,
  uta_archivo varchar(255) NOT NULL,
  	ipo varchar(20) DEFAULT 'Ingreso',
  echa_subida timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_imagen)
);

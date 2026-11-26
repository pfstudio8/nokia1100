-- Migración: Módulo Caja (Libro de Movimientos Inmutable y Cierre Ciego)
-- Sistema: Nokia 1100

CREATE TABLE IF NOT EXISTS `caja` (
  `id_caja` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `hora_apertura` VARCHAR(8) NOT NULL,
  `hora_cierre` VARCHAR(8) DEFAULT NULL,
  `monto_apertura` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `monto_cierre` DECIMAL(12,2) DEFAULT NULL,
  `monto_esperado` DECIMAL(12,2) DEFAULT NULL,
  `diferencia` DECIMAL(12,2) DEFAULT NULL,
  `estado` ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `observaciones` TEXT DEFAULT NULL,
  `id_usuario_apertura` INT(11) DEFAULT NULL,
  `id_usuario_cierre` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_caja`),
  KEY `idx_caja_estado` (`estado`),
  KEY `idx_caja_fecha` (`fecha`),
  KEY `fk_caja_usr_apertura` (`id_usuario_apertura`),
  KEY `fk_caja_usr_cierre` (`id_usuario_cierre`),
  CONSTRAINT `fk_caja_usr_apertura` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_caja_usr_cierre` FOREIGN KEY (`id_usuario_cierre`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `caja_movimiento` (
  `id_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
  `id_caja` INT(11) NOT NULL,
  `tipo` ENUM('in','out') NOT NULL DEFAULT 'in',
  `medio_pago` VARCHAR(50) NOT NULL DEFAULT 'Efectivo',
  `concepto` VARCHAR(100) NOT NULL,
  `detalle` VARCHAR(255) DEFAULT '',
  `motivo` VARCHAR(255) DEFAULT NULL,
  `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `id_venta` INT(11) DEFAULT NULL,
  `id_reparacion` INT(11) DEFAULT NULL,
  `id_movimiento_origen` INT(11) DEFAULT NULL,
  `hora` VARCHAR(8) NOT NULL,
  `id_usuario` INT(11) DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_caja_mov_idcaja` (`id_caja`),
  KEY `idx_caja_mov_tipo` (`tipo`),
  KEY `idx_caja_mov_medio` (`medio_pago`),
  KEY `idx_caja_mov_venta` (`id_venta`),
  KEY `idx_caja_mov_reparacion` (`id_reparacion`),
  KEY `idx_caja_mov_origen` (`id_movimiento_origen`),
  KEY `fk_caja_mov_usr` (`id_usuario`),
  CONSTRAINT `fk_caja_mov_caja` FOREIGN KEY (`id_caja`) REFERENCES `caja` (`id_caja`) ON DELETE CASCADE,
  CONSTRAINT `fk_caja_mov_usr` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

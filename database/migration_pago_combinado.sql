-- Migración: Pago Combinado (hasta 2 medios de pago) y Arqueo de Caja solo en Efectivo
-- Sistema: Nokia 1100

-- 1. Asegurar que la columna metodo_de_pago en `venta` soporte descripciones combinadas
ALTER TABLE `venta` MODIFY COLUMN `metodo_de_pago` VARCHAR(100) DEFAULT 'Efectivo';

-- 2. Tabla de desglose de pagos por venta (1 o 2 pagos por venta)
CREATE TABLE IF NOT EXISTS `venta_pago` (
  `id_pago` INT(11) NOT NULL AUTO_INCREMENT,
  `id_venta` INT(11) NOT NULL,
  `metodo_pago` VARCHAR(50) NOT NULL,
  `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  KEY `idx_venta_pago_venta` (`id_venta`),
  KEY `idx_venta_pago_metodo` (`metodo_pago`),
  CONSTRAINT `fk_venta_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Migrar ventas históricas que aún no tengan desglose en `venta_pago`
INSERT INTO `venta_pago` (`id_venta`, `metodo_pago`, `monto`)
SELECT v.id_venta,
       COALESCE(NULLIF(TRIM(v.metodo_de_pago), ''), 'Efectivo'),
       v.total
FROM `venta` v
LEFT JOIN `venta_pago` vp ON v.id_venta = vp.id_venta
WHERE vp.id_pago IS NULL AND v.total > 0;

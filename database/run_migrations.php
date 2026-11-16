<?php
// database/run_migrations.php
// Ejecuta por única vez las migraciones estructurales de Caja y Pago Combinado sin sobrecargar los modelos en tiempo de ejecución.

function apply_system_migrations(mysqli $conn): void
{
    $lockFile = __DIR__ . '/.schema_v3_immutable_caja.lock';
    if (file_exists($lockFile)) {
        return;
    }

    try {
        // 1. Ampliar metodo_de_pago en venta
        try {
            $conn->query("ALTER TABLE `venta` MODIFY COLUMN `metodo_de_pago` VARCHAR(100) DEFAULT 'Efectivo'");
        } catch (Throwable $e) {}

        // 2. Tabla venta_pago
        $conn->query("CREATE TABLE IF NOT EXISTS `venta_pago` (
          `id_pago` INT(11) NOT NULL AUTO_INCREMENT,
          `id_venta` INT(11) NOT NULL,
          `metodo_pago` VARCHAR(50) NOT NULL,
          `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_pago`),
          KEY `idx_venta_pago_venta` (`id_venta`),
          KEY `idx_venta_pago_metodo` (`metodo_pago`),
          CONSTRAINT `fk_venta_pago_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $conn->query("INSERT INTO `venta_pago` (`id_venta`, `metodo_pago`, `monto`)
                      SELECT v.id_venta,
                             COALESCE(NULLIF(TRIM(v.metodo_de_pago), ''), 'Efectivo'),
                             v.total
                      FROM `venta` v
                      LEFT JOIN `venta_pago` vp ON v.id_venta = vp.id_venta
                      WHERE vp.id_pago IS NULL AND v.total > 0");

        // 3. Tabla caja (monto_esperado NULL mientras está abierta; se congela solo al cerrar)
        $conn->query("CREATE TABLE IF NOT EXISTS `caja` (
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
          KEY `idx_caja_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $conn->query("ALTER TABLE `caja` MODIFY COLUMN `monto_esperado` DECIMAL(12,2) DEFAULT NULL");
            $conn->query("ALTER TABLE `caja` MODIFY COLUMN `diferencia` DECIMAL(12,2) DEFAULT NULL");
            $conn->query("UPDATE `caja` SET `monto_esperado` = NULL WHERE `estado` = 'abierta'");
        } catch (Throwable $e) {}

        // 4. Tabla caja_movimiento (libro inmutable con medio_pago, motivo, id_venta, id_reparacion, id_movimiento_origen y fecha_creacion DATETIME)
        $conn->query("CREATE TABLE IF NOT EXISTS `caja_movimiento` (
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
          KEY `idx_caja_mov_origen` (`id_movimiento_origen`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $alterCols = [
            'medio_pago'           => "ALTER TABLE `caja_movimiento` ADD COLUMN `medio_pago` VARCHAR(50) NOT NULL DEFAULT 'Efectivo' AFTER `tipo`",
            'motivo'               => "ALTER TABLE `caja_movimiento` ADD COLUMN `motivo` VARCHAR(255) DEFAULT NULL AFTER `detalle`",
            'id_venta'             => "ALTER TABLE `caja_movimiento` ADD COLUMN `id_venta` INT(11) DEFAULT NULL AFTER `monto`",
            'id_reparacion'         => "ALTER TABLE `caja_movimiento` ADD COLUMN `id_reparacion` INT(11) DEFAULT NULL AFTER `id_venta`",
            'id_movimiento_origen' => "ALTER TABLE `caja_movimiento` ADD COLUMN `id_movimiento_origen` INT(11) DEFAULT NULL AFTER `id_reparacion`",
            'fecha_creacion'       => "ALTER TABLE `caja_movimiento` ADD COLUMN `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
        ];

        foreach ($alterCols as $col => $sql) {
            $resCol = $conn->query("SHOW COLUMNS FROM `caja_movimiento` LIKE '$col'");
            if (!$resCol || $resCol->num_rows === 0) {
                try {
                    $conn->query($sql);
                } catch (Throwable $e) {}
            }
        }

        @file_put_contents($lockFile, date('c'));
    } catch (Throwable $e) {
        error_log("Migration warning: " . $e->getMessage());
    }
}

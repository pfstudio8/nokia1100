<?php
// database/run_migrations.php
// Ejecuta por única vez las migraciones estructurales de Caja, Pago Combinado, limpieza de DER y claves foráneas.

function apply_system_migrations(mysqli $conn): void
{
    $lockFile = __DIR__ . '/.schema_v6_remove_compra_iva.lock';
    if (file_exists($lockFile)) {
        return;
    }

    $addFkIfMissing = static function (
        mysqli $conn,
        string $table,
        string $column,
        string $constraintName,
        string $refTable,
        string $refColumn,
        string $onDelete = 'RESTRICT'
    ): void {
        try {
            $stmtChk = $conn->prepare(
                "SELECT CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );
            if ($stmtChk) {
                $stmtChk->bind_param("ss", $table, $column);
                $stmtChk->execute();
                $exists = $stmtChk->get_result()->num_rows > 0;
                $stmtChk->close();
                if ($exists) {
                    return;
                }
            }
            $conn->query(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}`
                 FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`) ON DELETE {$onDelete}"
            );
        } catch (Throwable $e) {
            error_log("FK migration warning ({$table}.{$column}): " . $e->getMessage());
        }
    };

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
          KEY `idx_caja_fecha` (`fecha`),
          KEY `fk_caja_usr_apertura` (`id_usuario_apertura`),
          KEY `fk_caja_usr_cierre` (`id_usuario_cierre`)
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
          KEY `idx_caja_mov_origen` (`id_movimiento_origen`),
          KEY `fk_caja_mov_usr` (`id_usuario`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $alterCols = [
            'medio_pago'           => "ALTER TABLE `caja_movimiento` ADD COLUMN `medio_pago` VARCHAR(50) NOT NULL DEFAULT 'Efectivo' AFTER `tipo`",
            'motivo'               => "ALTER TABLE `caja_movimiento` ADD COLUMN `motivo` VARCHAR(255) DEFAULT NULL AFTER `detalle`",
            'id_venta'             => "ALTER TABLE `caja_movimiento` ADD COLUMN `id_venta` INT(11) DEFAULT NULL AFTER `monto`",
            'id_reparacion'        => "ALTER TABLE `caja_movimiento` ADD COLUMN `id_reparacion` INT(11) DEFAULT NULL AFTER `id_venta`",
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

        // 5. Asegurar que los usuarios con rol 'empleado' que tenían el set por defecto anterior incluyan el módulo 'caja'
        try {
            $conn->query("UPDATE `usuario`
                          SET `modulos_permitidos` = 'dashboard,venta,caja,inventario,clientes'
                          WHERE `rol` = 'empleado'
                            AND (`modulos_permitidos` IS NULL OR TRIM(`modulos_permitidos`) = 'dashboard,venta,inventario,clientes')");
        } catch (Throwable $e) {}

        // 6. Limpieza de columnas/tablas redundantes (DER v5)
        // 6a. Eliminar usuario.motivo_baja
        try {
            $resMotivo = $conn->query("SHOW COLUMNS FROM `usuario` LIKE 'motivo_baja'");
            if ($resMotivo && $resMotivo->num_rows > 0) {
                $conn->query("ALTER TABLE `usuario` DROP COLUMN `motivo_baja`");
            }
        } catch (Throwable $e) {}

        // 6b. Eliminar detalle_venta.precio_copiado
        try {
            $resCopiado = $conn->query("SHOW COLUMNS FROM `detalle_venta` LIKE 'precio_copiado'");
            if ($resCopiado && $resCopiado->num_rows > 0) {
                $conn->query("ALTER TABLE `detalle_venta` DROP COLUMN `precio_copiado`");
            }
        } catch (Throwable $e) {}

        // 6c. Eliminar compra.iva
        try {
            $resIva = $conn->query("SHOW COLUMNS FROM `compra` LIKE 'iva'");
            if ($resIva && $resIva->num_rows > 0) {
                $conn->query("ALTER TABLE `compra` DROP COLUMN `iva`");
            }
        } catch (Throwable $e) {}

        // 6c. Consolidar producto_compra_historial dentro de detalle_compra
        try {
            $dcCols = [
                'nombre_producto' => "ALTER TABLE `detalle_compra` ADD COLUMN `nombre_producto` VARCHAR(150) DEFAULT NULL AFTER `precio_compra`",
                'marca'           => "ALTER TABLE `detalle_compra` ADD COLUMN `marca` VARCHAR(100) DEFAULT NULL AFTER `nombre_producto`",
                'modelo'          => "ALTER TABLE `detalle_compra` ADD COLUMN `modelo` VARCHAR(100) DEFAULT NULL AFTER `marca`",
            ];
            foreach ($dcCols as $col => $sql) {
                $resDcCol = $conn->query("SHOW COLUMNS FROM `detalle_compra` LIKE '$col'");
                if (!$resDcCol || $resDcCol->num_rows === 0) {
                    $conn->query($sql);
                }
            }

            $resPch = $conn->query("SHOW TABLES LIKE 'producto_compra_historial'");
            if ($resPch && $resPch->num_rows > 0) {
                $conn->query(
                    "UPDATE `detalle_compra` dc
                     INNER JOIN `producto_compra_historial` pch ON dc.id_detalle_compra = pch.id_detalle_compra
                     SET dc.nombre_producto = COALESCE(dc.nombre_producto, pch.nombre_producto),
                         dc.marca = COALESCE(dc.marca, pch.marca),
                         dc.modelo = COALESCE(dc.modelo, pch.modelo)"
                );
                $conn->query("DROP TABLE IF EXISTS `producto_compra_historial`");
            }
        } catch (Throwable $e) {}

        // 7. Claves foráneas faltantes para conectar el DER completo (Taller + Caja)
        // Limpiar referencias huérfanas previas (ej. id_usuario = 0 o registros borrados antes de tener FK) en columnas anulables
        $nullableCleanup = [
            "UPDATE `reparacion` r LEFT JOIN `usuario` u ON r.id_usuario_recibe = u.id_usuario SET r.id_usuario_recibe = NULL WHERE r.id_usuario_recibe IS NOT NULL AND u.id_usuario IS NULL",
            "UPDATE `reparacion_historial` rh LEFT JOIN `usuario` u ON rh.id_usuario = u.id_usuario SET rh.id_usuario = NULL WHERE rh.id_usuario IS NOT NULL AND u.id_usuario IS NULL",
            "UPDATE `caja` c LEFT JOIN `usuario` u ON c.id_usuario_apertura = u.id_usuario SET c.id_usuario_apertura = NULL WHERE c.id_usuario_apertura IS NOT NULL AND u.id_usuario IS NULL",
            "UPDATE `caja` c LEFT JOIN `usuario` u ON c.id_usuario_cierre = u.id_usuario SET c.id_usuario_cierre = NULL WHERE c.id_usuario_cierre IS NOT NULL AND u.id_usuario IS NULL",
            "UPDATE `caja_movimiento` m LEFT JOIN `usuario` u ON m.id_usuario = u.id_usuario SET m.id_usuario = NULL WHERE m.id_usuario IS NOT NULL AND u.id_usuario IS NULL",
            "UPDATE `caja_movimiento` m LEFT JOIN `venta` v ON m.id_venta = v.id_venta SET m.id_venta = NULL WHERE m.id_venta IS NOT NULL AND v.id_venta IS NULL",
            "UPDATE `caja_movimiento` m LEFT JOIN `reparacion` r ON m.id_reparacion = r.id_reparacion SET m.id_reparacion = NULL WHERE m.id_reparacion IS NOT NULL AND r.id_reparacion IS NULL",
            "UPDATE `caja_movimiento` m LEFT JOIN `caja_movimiento` o ON m.id_movimiento_origen = o.id_movimiento SET m.id_movimiento_origen = NULL WHERE m.id_movimiento_origen IS NOT NULL AND o.id_movimiento IS NULL",
        ];
        foreach ($nullableCleanup as $cleanSql) {
            try {
                $conn->query($cleanSql);
            } catch (Throwable $e) {}
        }

        try {
            $resIdx = $conn->query("SHOW INDEX FROM `reparacion` WHERE Key_name = 'uq_reparacion_codigo'");
            if (!$resIdx || $resIdx->num_rows === 0) {
                $conn->query("ALTER TABLE `reparacion` ADD UNIQUE KEY `uq_reparacion_codigo` (`codigo_orden`)");
            }
        } catch (Throwable $e) {}

        $addFkIfMissing($conn, 'reparacion', 'id_cliente', 'fk_reparacion_cliente', 'cliente', 'id_cliente', 'RESTRICT');
        $addFkIfMissing($conn, 'reparacion', 'id_usuario_recibe', 'fk_reparacion_usuario', 'usuario', 'id_usuario', 'SET NULL');
        $addFkIfMissing($conn, 'reparacion_historial', 'id_reparacion', 'fk_rep_hist_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        $addFkIfMissing($conn, 'reparacion_historial', 'id_usuario', 'fk_rep_hist_usuario', 'usuario', 'id_usuario', 'SET NULL');
        $addFkIfMissing($conn, 'reparacion_imagen', 'id_reparacion', 'fk_rep_img_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        $addFkIfMissing($conn, 'reparacion_repuesto', 'id_reparacion', 'fk_rep_rep_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        $addFkIfMissing($conn, 'reparacion_repuesto', 'id_producto', 'fk_rep_rep_producto', 'producto', 'id_producto', 'RESTRICT');

        $addFkIfMissing($conn, 'caja', 'id_usuario_apertura', 'fk_caja_usr_apertura', 'usuario', 'id_usuario', 'SET NULL');
        $addFkIfMissing($conn, 'caja', 'id_usuario_cierre', 'fk_caja_usr_cierre', 'usuario', 'id_usuario', 'SET NULL');
        $addFkIfMissing($conn, 'caja_movimiento', 'id_caja', 'fk_caja_mov_caja', 'caja', 'id_caja', 'CASCADE');
        $addFkIfMissing($conn, 'caja_movimiento', 'id_usuario', 'fk_caja_mov_usr', 'usuario', 'id_usuario', 'SET NULL');
        $addFkIfMissing($conn, 'caja_movimiento', 'id_venta', 'fk_caja_mov_venta', 'venta', 'id_venta', 'SET NULL');
        $addFkIfMissing($conn, 'caja_movimiento', 'id_reparacion', 'fk_caja_mov_reparacion', 'reparacion', 'id_reparacion', 'SET NULL');
        $addFkIfMissing($conn, 'caja_movimiento', 'id_movimiento_origen', 'fk_caja_mov_origen', 'caja_movimiento', 'id_movimiento', 'SET NULL');

        @unlink(__DIR__ . '/.schema_v3_immutable_caja.lock');
        @unlink(__DIR__ . '/.schema_v4_immutable_caja.lock');
        @unlink(__DIR__ . '/.schema_v5_clean_der.lock');
        @file_put_contents($lockFile, date('c'));
    } catch (Throwable $e) {
        error_log("Migration warning: " . $e->getMessage());
    }
}

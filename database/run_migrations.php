<?php
// database/run_migrations.php
// Ejecuta las migraciones estructurales idempotentes del sistema (v6 + v7).

function add_fk_if_missing(
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
}

function add_column_if_missing(mysqli $conn, string $table, string $column, string $alterSql): bool
{
    try {
        $stmt = $conn->prepare(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param("ss", $table, $column);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            if ($exists) {
                return false;
            }
        }
        $conn->query($alterSql);
        return true;
    } catch (Throwable $e) {
        error_log("Column migration warning ({$table}.{$column}): " . $e->getMessage());
        return false;
    }
}

function apply_system_migrations(mysqli $conn, bool $isTestRun = false): void
{
    apply_v6_migrations($conn, $isTestRun);
    apply_v7_feature_migrations($conn, $isTestRun);
}

function apply_v6_migrations(mysqli $conn, bool $isTestRun = false): void
{
    $lockFile = __DIR__ . '/.schema_v6_remove_compra_iva.lock';
    if (!$isTestRun && file_exists($lockFile)) {
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
          KEY `idx_caja_fecha` (`fecha`),
          KEY `fk_caja_usr_apertura` (`id_usuario_apertura`),
          KEY `fk_caja_usr_cierre` (`id_usuario_cierre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $conn->query("ALTER TABLE `caja` MODIFY COLUMN `monto_esperado` DECIMAL(12,2) DEFAULT NULL");
            $conn->query("ALTER TABLE `caja` MODIFY COLUMN `diferencia` DECIMAL(12,2) DEFAULT NULL");
            $conn->query("UPDATE `caja` SET `monto_esperado` = NULL WHERE `estado` = 'abierta'");
        } catch (Throwable $e) {}

        // 4. Tabla caja_movimiento
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
            add_column_if_missing($conn, 'caja_movimiento', $col, $sql);
        }

        // 5. Asegurar módulo 'caja' en empleados con set anterior por defecto
        try {
            $conn->query("UPDATE `usuario`
                          SET `modulos_permitidos` = 'dashboard,venta,caja,inventario,clientes'
                          WHERE `rol` = 'empleado'
                            AND (`modulos_permitidos` IS NULL OR TRIM(`modulos_permitidos`) = 'dashboard,venta,inventario,clientes')");
        } catch (Throwable $e) {}

        // 6. Limpieza de columnas/tablas redundantes (DER v5)
        try {
            $resMotivo = $conn->query("SHOW COLUMNS FROM `usuario` LIKE 'motivo_baja'");
            if ($resMotivo && $resMotivo->num_rows > 0) {
                $conn->query("ALTER TABLE `usuario` DROP COLUMN `motivo_baja`");
            }
        } catch (Throwable $e) {}

        try {
            $resCopiado = $conn->query("SHOW COLUMNS FROM `detalle_venta` LIKE 'precio_copiado'");
            if ($resCopiado && $resCopiado->num_rows > 0) {
                $conn->query("ALTER TABLE `detalle_venta` DROP COLUMN `precio_copiado`");
            }
        } catch (Throwable $e) {}

        try {
            $resIva = $conn->query("SHOW COLUMNS FROM `compra` LIKE 'iva'");
            if ($resIva && $resIva->num_rows > 0) {
                $conn->query("ALTER TABLE `compra` DROP COLUMN `iva`");
            }
        } catch (Throwable $e) {}

        try {
            $dcCols = [
                'nombre_producto' => "ALTER TABLE `detalle_compra` ADD COLUMN `nombre_producto` VARCHAR(150) DEFAULT NULL AFTER `precio_compra`",
                'marca'           => "ALTER TABLE `detalle_compra` ADD COLUMN `marca` VARCHAR(100) DEFAULT NULL AFTER `nombre_producto`",
                'modelo'          => "ALTER TABLE `detalle_compra` ADD COLUMN `modelo` VARCHAR(100) DEFAULT NULL AFTER `marca`",
            ];
            foreach ($dcCols as $col => $sql) {
                add_column_if_missing($conn, 'detalle_compra', $col, $sql);
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

        // 7. Claves foráneas faltantes
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

        add_fk_if_missing($conn, 'reparacion', 'id_cliente', 'fk_reparacion_cliente', 'cliente', 'id_cliente', 'RESTRICT');
        add_fk_if_missing($conn, 'reparacion', 'id_usuario_recibe', 'fk_reparacion_usuario', 'usuario', 'id_usuario', 'SET NULL');
        add_fk_if_missing($conn, 'reparacion_historial', 'id_reparacion', 'fk_rep_hist_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        add_fk_if_missing($conn, 'reparacion_historial', 'id_usuario', 'fk_rep_hist_usuario', 'usuario', 'id_usuario', 'SET NULL');
        add_fk_if_missing($conn, 'reparacion_imagen', 'id_reparacion', 'fk_rep_img_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        add_fk_if_missing($conn, 'reparacion_repuesto', 'id_reparacion', 'fk_rep_rep_reparacion', 'reparacion', 'id_reparacion', 'CASCADE');
        add_fk_if_missing($conn, 'reparacion_repuesto', 'id_producto', 'fk_rep_rep_producto', 'producto', 'id_producto', 'RESTRICT');

        add_fk_if_missing($conn, 'caja', 'id_usuario_apertura', 'fk_caja_usr_apertura', 'usuario', 'id_usuario', 'SET NULL');
        add_fk_if_missing($conn, 'caja', 'id_usuario_cierre', 'fk_caja_usr_cierre', 'usuario', 'id_usuario', 'SET NULL');
        add_fk_if_missing($conn, 'caja_movimiento', 'id_caja', 'fk_caja_mov_caja', 'caja', 'id_caja', 'CASCADE');
        add_fk_if_missing($conn, 'caja_movimiento', 'id_usuario', 'fk_caja_mov_usr', 'usuario', 'id_usuario', 'SET NULL');
        add_fk_if_missing($conn, 'caja_movimiento', 'id_venta', 'fk_caja_mov_venta', 'venta', 'id_venta', 'SET NULL');
        add_fk_if_missing($conn, 'caja_movimiento', 'id_reparacion', 'fk_caja_mov_reparacion', 'reparacion', 'id_reparacion', 'SET NULL');
        add_fk_if_missing($conn, 'caja_movimiento', 'id_movimiento_origen', 'fk_caja_mov_origen', 'caja_movimiento', 'id_movimiento', 'SET NULL');

        if (!$isTestRun) {
            @unlink(__DIR__ . '/.schema_v3_immutable_caja.lock');
            @unlink(__DIR__ . '/.schema_v4_immutable_caja.lock');
            @unlink(__DIR__ . '/.schema_v5_clean_der.lock');
            @file_put_contents($lockFile, date('c'));
        }
    } catch (Throwable $e) {
        error_log("Migration v6 warning: " . $e->getMessage());
    }
}

function apply_v7_feature_migrations(mysqli $conn, bool $isTestRun = false): void
{
    $lockFile = __DIR__ . '/.schema_v7_features.lock';
    if (!$isTestRun && file_exists($lockFile)) {
        return;
    }

    try {
        // 1. Garantías e IMEI en reparacion
        add_column_if_missing($conn, 'reparacion', 'garantia_hasta', "ALTER TABLE `reparacion` ADD COLUMN `garantia_hasta` DATE NULL AFTER `fecha_entrega`");
        add_column_if_missing($conn, 'reparacion', 'es_garantia', "ALTER TABLE `reparacion` ADD COLUMN `es_garantia` TINYINT(1) NOT NULL DEFAULT 0 AFTER `garantia_hasta`");
        add_column_if_missing($conn, 'reparacion', 'id_reparacion_origen', "ALTER TABLE `reparacion` ADD COLUMN `id_reparacion_origen` INT(11) NULL AFTER `es_garantia`");
        add_column_if_missing($conn, 'reparacion', 'garantia_anulada', "ALTER TABLE `reparacion` ADD COLUMN `garantia_anulada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `id_reparacion_origen`");
        add_column_if_missing($conn, 'reparacion', 'garantia_motivo_anulacion', "ALTER TABLE `reparacion` ADD COLUMN `garantia_motivo_anulacion` VARCHAR(255) NULL AFTER `garantia_anulada`");

        try {
            $resIdxImei = $conn->query("SHOW INDEX FROM `reparacion` WHERE Key_name = 'idx_reparacion_imei'");
            if (!$resIdxImei || $resIdxImei->num_rows === 0) {
                $conn->query("ALTER TABLE `reparacion` ADD KEY `idx_reparacion_imei` (`equipo_imei`)");
            }
        } catch (Throwable $e) {}

        add_fk_if_missing($conn, 'reparacion', 'id_reparacion_origen', 'fk_reparacion_origen', 'reparacion', 'id_reparacion', 'SET NULL');

        // Backfill de garantía 20 días para reparaciones ya entregadas
        try {
            $conn->query(
                "UPDATE `reparacion`
                 SET `garantia_hasta` = DATE_ADD(DATE(`fecha_entrega`), INTERVAL 20 DAY)
                 WHERE `estado` = 'Entregado' AND `fecha_entrega` IS NOT NULL AND `garantia_hasta` IS NULL"
            );
        } catch (Throwable $e) {}

        // 2. venta.descuento
        add_column_if_missing($conn, 'venta', 'descuento', "ALTER TABLE `venta` ADD COLUMN `descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total`");

        // 3. Tabla notificacion_email
        try {
            $conn->query(
                "CREATE TABLE IF NOT EXISTS `notificacion_email` (
                  `id_notificacion` INT(11) NOT NULL AUTO_INCREMENT,
                  `id_reparacion` INT(11) DEFAULT NULL,
                  `tipo` VARCHAR(40) NOT NULL,
                  `destinatario` VARCHAR(150) NOT NULL,
                  `asunto` VARCHAR(200) NOT NULL,
                  `estado` ENUM('enviado','fallido','omitido') NOT NULL DEFAULT 'enviado',
                  `detalle` VARCHAR(255) DEFAULT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id_notificacion`),
                  KEY `idx_ne_reparacion` (`id_reparacion`),
                  CONSTRAINT `fk_ne_reparacion` FOREIGN KEY (`id_reparacion`) REFERENCES `reparacion` (`id_reparacion`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
            error_log("notificacion_email migration warning: " . $e->getMessage());
        }

        // 4. Preservar permisos granulares para empleados existentes
        try {
            $resUsers = $conn->query(
                "SELECT id_usuario, modulos_permitidos
                 FROM usuario
                 WHERE rol = 'empleado'
                   AND modulos_permitidos IS NOT NULL
                   AND TRIM(modulos_permitidos) <> ''"
            );
            if ($resUsers) {
                $stmtUpd = $conn->prepare("UPDATE usuario SET modulos_permitidos = ? WHERE id_usuario = ?");
                while ($uRow = $resUsers->fetch_assoc()) {
                    $perms = array_values(array_filter(array_map('trim', explode(',', (string)$uRow['modulos_permitidos']))));
                    $original = $perms;

                    if ((in_array('venta', $perms, true) || in_array('ventas', $perms, true)) && !in_array('venta_anular', $perms, true)) {
                        $perms[] = 'venta_anular';
                    }
                    if (in_array('caja', $perms, true) && !in_array('caja_movimientos', $perms, true)) {
                        $perms[] = 'caja_movimientos';
                    }
                    if (in_array('reportes', $perms, true) && !in_array('reportes_financieros', $perms, true)) {
                        $perms[] = 'reportes_financieros';
                    }

                    if ($perms !== $original && $stmtUpd) {
                        $joined = implode(',', array_values(array_unique($perms)));
                        $uid = (int)$uRow['id_usuario'];
                        $stmtUpd->bind_param("si", $joined, $uid);
                        $stmtUpd->execute();
                    }
                }
                if ($stmtUpd) {
                    $stmtUpd->close();
                }
            }
        } catch (Throwable $e) {
            error_log("Permissions migration warning: " . $e->getMessage());
        }

        if (!$isTestRun) {
            @file_put_contents($lockFile, date('c'));
        }
    } catch (Throwable $e) {
        error_log("Migration v7 warning: " . $e->getMessage());
    }
}

-- ============================================================
-- MIGRACIÓN: Seguridad y Auditoría + Baja Lógica de Usuarios
-- Nokia 1100 System
-- ============================================================

-- 1. Agregar baja lógica a la tabla usuario
ALTER TABLE `usuario`
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `verificado`,
    ADD COLUMN `fecha_baja` DATETIME DEFAULT NULL AFTER `is_active`,
    ADD COLUMN `motivo_baja` VARCHAR(255) DEFAULT NULL AFTER `fecha_baja`;

-- 2. Todos los usuarios existentes quedan activos
UPDATE `usuario` SET `is_active` = 1 WHERE `is_active` IS NULL;

-- 3. Tabla de auditoría de seguridad
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id_log`          INT(11)       NOT NULL AUTO_INCREMENT,
    `id_usuario`      INT(11)       DEFAULT NULL COMMENT 'NULL si el intento fue fallido o sin sesión',
    `accion`          VARCHAR(100)  NOT NULL COMMENT 'LOGIN_OK, LOGIN_FAIL, LOGOUT, USER_CREATE, USER_DELETE, USER_RESTORE, ROLE_CHANGE, SALE_CREATE, PRODUCT_CREATE, PRODUCT_DELETE, PASSWORD_RESET',
    `tabla_afectada`  VARCHAR(50)   DEFAULT NULL,
    `id_registro`     INT(11)       DEFAULT NULL,
    `descripcion`     TEXT          DEFAULT NULL,
    `ip`              VARCHAR(45)   DEFAULT NULL,
    `user_agent`      VARCHAR(255)  DEFAULT NULL,
    `username_intent` VARCHAR(100)  DEFAULT NULL COMMENT 'Usuario que intentó el login (aunque falle)',
    `fecha`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log`),
    KEY `idx_usuario`  (`id_usuario`),
    KEY `idx_accion`   (`accion`),
    KEY `idx_fecha`    (`fecha`),
    CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`id_usuario`)
        REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de auditoría de seguridad y acciones sensibles';

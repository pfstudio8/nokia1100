-- database/migration_rollback.sql
-- Agregar columna 'estado' a la tabla 'venta' para permitir anulación (rollback) de ventas.
ALTER TABLE venta 
ADD COLUMN estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada' AFTER id_usuario;

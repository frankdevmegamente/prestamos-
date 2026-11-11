-- Migración: modo mantenimiento (ejecutar una vez)
-- Permite al admin bloquear el acceso de escritura/operación a usuarios no-admin.

ALTER TABLE configuracion ADD COLUMN mantenimiento TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=sistema en mantenimiento';
ALTER TABLE configuracion ADD COLUMN mantenimiento_mensaje VARCHAR(255) DEFAULT 'El sistema se está actualizando. Vuelva más tarde.' COMMENT 'Mensaje mostrado a usuarios no-admin';
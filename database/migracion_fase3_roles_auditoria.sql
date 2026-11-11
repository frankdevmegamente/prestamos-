-- Migración Fase 3: roles, auditoría (ejecutar una vez)
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS rol ENUM('admin','editor','cajero','lector') NOT NULL DEFAULT 'admin';
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS fecha_actualizacion DATETIME NULL;

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NULL,
    accion VARCHAR(100) NOT NULL,
    tabla VARCHAR(50) NULL,
    registro_id INT NULL,
    detalle TEXT NULL,
    ip VARCHAR(45) NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aud_fecha (fecha),
    INDEX idx_aud_user (user_id),
    INDEX idx_aud_tabla (tabla, registro_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

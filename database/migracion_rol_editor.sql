-- Migración: rol editor (ejecutar una vez)
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin','editor','cajero','lector') NOT NULL DEFAULT 'admin';
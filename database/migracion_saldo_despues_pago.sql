-- Migración: Agregar columna saldo_despues_pago a la tabla pagos
-- Ejecutar este script una sola vez para actualizar la base de datos existente

USE prestamos_db;

-- Agregar columna saldo_despues_pago
ALTER TABLE pagos ADD COLUMN saldo_despues_pago DECIMAL(12,2) DEFAULT NULL AFTER fecha_registro;

-- Opcional: Calcular saldos históricos para pagos existentes
-- (Esto es aproximado ya que no tenemos el saldo exacto histórico)
UPDATE pagos pg
INNER JOIN prestamos p ON pg.prestamo_id = p.id
SET pg.saldo_despues_pago = p.saldo_pendiente
WHERE pg.saldo_despues_pago IS NULL;

-- Migración: préstamos indefinidos (sin plazo) + mora por inactividad (ejecutar una vez)
-- Préstamo indefinido: plazo_meses = 0, num_cuotas = 0, cuota_mensual = 0,
-- total_intereses = 0, total_pagar = monto, fecha_vencimiento = NULL.
-- Mora por inactividad: días desde última actividad (último pago o desembolso)
-- comparados contra dias_gracia + dias_inactividad.

ALTER TABLE prestamos MODIFY fecha_vencimiento DATE NULL;

ALTER TABLE configuracion ADD COLUMN dias_inactividad INT NOT NULL DEFAULT 30;

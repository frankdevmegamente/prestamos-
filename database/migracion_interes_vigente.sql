-- Migración: interés vigente con devengo diario + tipos de pago (ejecutar una vez)
-- interes_vigente: interés causado no pagado. Se devenga a diario sobre el saldo
-- (saldo × tasa_mensual/100 / 30 × días) y los pagos lo cubren primero.
-- tipo_pago: cuota (normal) | interes (solo interés) | abono (solo capital).

ALTER TABLE prestamos
    ADD COLUMN interes_vigente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN fecha_ultimo_devengo DATE NULL;

-- Backfill: último devengo = último pago o desembolso (no se inventa interés pasado)
UPDATE prestamos p
SET p.fecha_ultimo_devengo = COALESCE(
    (SELECT MAX(pg.fecha_pago) FROM pagos pg WHERE pg.prestamo_id = p.id),
    p.fecha_prestamo
);

ALTER TABLE pagos
    ADD COLUMN tipo_pago ENUM('cuota','interes','abono') NOT NULL DEFAULT 'cuota';

-- Backfill: abonos históricos marcados con es_abono_capital
UPDATE pagos SET tipo_pago = 'abono' WHERE es_abono_capital = 1;

-- Migración: días de gracia de interés (ejecutar una vez)
-- Ventana por corte mensual para pagar sin que se genere el interés de ese mes.
-- Las filas existentes toman el valor por defecto (3 días).

ALTER TABLE configuracion ADD COLUMN dias_gracia_interes INT NOT NULL DEFAULT 3;

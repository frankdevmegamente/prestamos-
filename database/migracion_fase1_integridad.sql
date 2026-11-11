-- Migración Fase 1: integridad y concurrencia (ejecutar una vez)
-- Evita numero_cuota duplicado por doble-click y acelera reportes.

-- UNIQUE para idempotencia de cuotas (ignora duplicados preexistentes: conservar el primero)
-- Si hay duplicados, limpiarlos manualmente antes de aplicar el UNIQUE.
ALTER TABLE pagos ADD UNIQUE KEY uq_pagos_prestamo_cuota (prestamo_id, numero_cuota);

-- Índices para reportes/dashboard
CREATE INDEX idx_prestamos_estado_venc ON prestamos (estado, fecha_vencimiento);
CREATE INDEX idx_prestamos_fecha_prestamo ON prestamos (fecha_prestamo);
CREATE INDEX idx_pagos_fecha ON pagos (fecha_pago);
CREATE INDEX idx_pagos_deudas_fecha ON pagos_deudas (fecha_pago);
CREATE INDEX idx_mis_deudas_estado ON mis_deudas (estado);

-- FK faltante: prestamos.acreedor_id -> acreedores.id (solo si no hay huérfanos)
-- Verificar antes: SELECT * FROM prestamos WHERE acreedor_id IS NOT NULL AND acreedor_id NOT IN (SELECT id FROM acreedores);
-- ALTER TABLE prestamos ADD CONSTRAINT fk_prestamos_acreedor FOREIGN KEY (acreedor_id) REFERENCES acreedores(id) ON DELETE RESTRICT;
-- CREATE INDEX idx_prestamos_acreedor ON prestamos (acreedor_id);

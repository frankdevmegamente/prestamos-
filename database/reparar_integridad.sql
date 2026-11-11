-- ============================================================
-- Reparación de integridad referencial (ejecutar una vez en phpMyAdmin)
-- La BD real NO tiene las FKs/UNIQUE declaradas en prestamos.sql.
-- Ejecutar PRIMERO las consultas de verificación; si devuelven filas,
-- limpiar huérfanos antes de continuar (cada ALTER fallará si los hay).
-- ============================================================

-- ------------------------------------------------------------
-- 0) VERIFICACIONES PREVIAS (deben devolver 0 filas)
-- ------------------------------------------------------------
-- SELECT * FROM prestamos WHERE cliente_id NOT IN (SELECT id FROM clientes);
-- SELECT * FROM prestamos WHERE acreedor_id IS NOT NULL AND acreedor_id NOT IN (SELECT id FROM acreedores);
-- SELECT * FROM pagos WHERE prestamo_id NOT IN (SELECT id FROM prestamos);
-- SELECT * FROM mis_deudas WHERE acreedor_id NOT IN (SELECT id FROM acreedores);
-- SELECT * FROM pagos_deudas WHERE deuda_id NOT IN (SELECT id FROM mis_deudas);

-- ------------------------------------------------------------
-- 1) FOREIGN KEYS (ON DELETE RESTRICT)
-- Nota: si alguna tabla no existe en su BD, comentar la línea.
-- ------------------------------------------------------------
ALTER TABLE prestamos
  ADD CONSTRAINT fk_prestamos_cliente
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT;

ALTER TABLE prestamos
  ADD CONSTRAINT fk_prestamos_acreedor
  FOREIGN KEY (acreedor_id) REFERENCES acreedores(id) ON DELETE RESTRICT;

ALTER TABLE pagos
  ADD CONSTRAINT fk_pagos_prestamo
  FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT;

ALTER TABLE mis_deudas
  ADD CONSTRAINT fk_mis_deudas_acreedor
  FOREIGN KEY (acreedor_id) REFERENCES acreedores(id) ON DELETE RESTRICT;

ALTER TABLE pagos_deudas
  ADD CONSTRAINT fk_pagos_deudas_deuda
  FOREIGN KEY (deuda_id) REFERENCES mis_deudas(id) ON DELETE RESTRICT;

-- ------------------------------------------------------------
-- 2) UNIQUE en cedula de clientes
-- Importante: primero depurar duplicados si los hay:
--   SELECT cedula, COUNT(*) n FROM clientes GROUP BY cedula HAVING n > 1;
-- ------------------------------------------------------------
ALTER TABLE clientes ADD UNIQUE KEY uq_clientes_cedula (cedula);

-- ------------------------------------------------------------
-- 3) ÍNDICES de reportes (si no existen ya)
-- ------------------------------------------------------------
CREATE INDEX idx_prestamos_estado_venc ON prestamos (estado, fecha_vencimiento);
CREATE INDEX idx_prestamos_fecha_prestamo ON prestamos (fecha_prestamo);
CREATE INDEX idx_pagos_fecha ON pagos (fecha_pago);
CREATE INDEX idx_pagos_deudas_fecha ON pagos_deudas (fecha_pago);
CREATE INDEX idx_mis_deudas_estado ON mis_deudas (estado);
CREATE INDEX idx_prestamos_acreedor ON prestamos (acreedor_id);

-- ------------------------------------------------------------
-- NOTA sobre uq_pagos_prestamo_cuota (prestamo_id, numero_cuota):
-- NO se aplica a propósito. Los pagos tipo "abono" reciben el mismo
-- numero_cuota que la próxima cuota (contador excluye abonos), por lo
-- que un UNIQUE violaría al combinar abonos y cuotas. La numeración
-- excluyendo abonos ya quedó corregida en prestamos/pagar.php.
-- ------------------------------------------------------------

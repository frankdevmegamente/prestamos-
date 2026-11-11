-- ============================================
-- MIGRACION: Control de interés pagado por mes
-- Agrega columna interes_pagado_hasta a mis_deudas
-- ============================================

USE prestamos_db;

ALTER TABLE mis_deudas
  ADD COLUMN interes_pagado_hasta DATE DEFAULT NULL
  AFTER interes_mensual;

-- Inicializar 1 mes antes de fecha_inicio (para que el primer mes de interés cuente)
UPDATE mis_deudas SET interes_pagado_hasta = DATE_SUB(fecha_inicio, INTERVAL 1 MONTH) WHERE interes_pagado_hasta IS NULL;

SELECT 'Migracion completada: columna interes_pagado_hasta agregada' AS resultado;

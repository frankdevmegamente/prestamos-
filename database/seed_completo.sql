-- ============================================
-- SEED COMPLETO: LIMPIEZA + DATOS DE PRUEBA
-- Limpia TODA la BD y crea datos variados
-- Fecha base: 2026-07-23
-- ============================================

USE prestamos_db;

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE pagos_deudas;
TRUNCATE TABLE mis_deudas;
TRUNCATE TABLE pagos;
TRUNCATE TABLE prestamos;
TRUNCATE TABLE acreedores;
TRUNCATE TABLE clientes;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- USUARIO ADMIN (se mantiene)
-- ============================================
DELETE FROM usuarios WHERE username != 'admin';

-- ============================================
-- CLIENTES (8 clientes variados)
-- ============================================
INSERT INTO clientes (id, nombre, apellido, cedula, telefono, direccion, email, referencia_personal, fecha_registro, estado) VALUES
(1, 'María Elena', 'García López', '12345678', '5551234567', 'Av. Reforma 123, Col. Centro', 'maria.garcia@email.com', 'Juan García - 5559876543', '2025-10-15 10:00:00', 'activo'),
(2, 'Roberto Carlos', 'Hernández Cruz', '23456789', '5552345678', 'Calle Juárez 456, Col. Norte', 'roberto.h@email.com', 'Pedro Hernández - 5558765432', '2025-11-20 11:30:00', 'activo'),
(3, 'Ana Patricia', 'Martínez Silva', '34567890', '5553456789', 'Blvd. Insurgentes 789, Col. Sur', 'ana.martinez@email.com', 'Laura Silva - 5557654321', '2026-01-10 09:15:00', 'activo'),
(4, 'Carlos Eduardo', 'Rodríguez Díaz', '45678901', '5554567890', 'Calle Morelos 321, Col. Este', 'carlos.rodriguez@email.com', 'Mario Díaz - 5556543210', '2025-09-05 14:00:00', 'activo'),
(5, 'Laura Sofía', 'Sánchez Ruiz', '56789012', '5555678901', 'Av. Universidad 654, Col. Oeste', 'laura.sanchez@email.com', 'Carmen Ruiz - 5555432109', '2026-02-18 08:45:00', 'activo'),
(6, 'Fernando', 'Torres Luna', '67890123', '5556789012', 'Calle Hidalgo 987, Col. Jardín', 'fernando.torres@email.com', 'Roberto Luna - 5554321098', '2026-03-25 16:20:00', 'activo'),
(7, 'Patricia Alejandra', 'Flores Castro', '78901234', '5557890123', 'Blvd. López Mateos 147, Col. Roma', 'patricia.f@email.com', 'Sofía Castro - 5553210987', '2025-08-12 12:00:00', 'activo'),
(8, 'Diego Armando', 'Morales Vega', '89012345', '5558901234', 'Av. Insurgentes 258, Col. Condesa', 'diego.morales@email.com', 'Andrés Vega - 5552109876', '2026-04-01 10:30:00', 'activo'),
(9, 'Isabel Cristina', 'Torres Blanco', '90123456', '5559012345', 'Calle Gobernador 369, Col. Centro', 'isabel.t@email.com', 'Elena Blanco - 5551098765', '2026-05-15 09:00:00', 'activo'),
(10, 'Miguel Ángel', 'Ríos Pereira', '01234567', '5550123456', 'Calle Madero 741, Col. Juárez', 'miguel.rios@email.com', 'Jorge Pereira - 5550987654', '2025-07-20 11:00:00', 'activo');

-- ============================================
-- ACREEDORES (4 acreedores)
-- ============================================
INSERT INTO acreedores (id, nombre, telefono, email, rfc, ciudad, notas, fecha_registro, estado) VALUES
(1, 'Banco Nacional S.A.', '5551112233', 'creditos@bancona.com', 'BNA850101XXX', 'Ciudad de México', 'Banco principal - Línea de crédito aprobada', '2025-06-01 10:00:00', 'activo'),
(2, 'Financiera del Norte S.A.', '5552223344', 'prestamos@findelno.com', 'FNO920515XXX', 'Monterrey', 'Financiera para crédito comercial', '2025-09-15 14:00:00', 'activo'),
(3, 'Juan Pérez Martínez', '5553334455', 'juan.perez@gmail.com', NULL, 'Guadalajara', 'Préstamo personal - Amigo de confianza', '2026-01-05 09:30:00', 'activo'),
(4, 'Grupo Financiero MX S.C.', '5554445566', 'inversiones@gfmx.com', 'GFM030320XXX', 'Puebla', 'Institucional - Fondos de inversión', '2025-11-01 11:00:00', 'activo');

-- ============================================
-- PRÉSTAMOS (12 préstamos en todos los estados)
-- ============================================
-- Columnas nuevas del híbrido: fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual

-- === ACTIVOS (5) ===
-- Préstamo 1: María García - Cuota fija 12 meses, propio
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (1, 1, 50000.00, 10.00, 12, 'meses', 12, 7339.18, 38070.16, 88070.16, 66052.62, 0.00, '2026-05-01', '2027-05-01', 'activo', 'Préstamo para negocio de ropa', 'propio', NULL, NULL, 0.00, 5000.00, '2026-05-01 10:00:00');

-- Préstamo 2: Roberto Hernández - Cuota fija 6 meses, propio
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (2, 2, 25000.00, 12.00, 6, 'meses', 6, 6081.08, 11486.48, 36486.48, 24324.32, 0.00, '2026-05-01', '2026-11-01', 'activo', 'Capital para taller mecánico', 'propio', NULL, NULL, 0.00, 3000.00, '2026-05-01 11:00:00');

-- Préstamo 3: Laura Sánchez - Cuota fija 6 meses, propio (nuevo, sin pagos)
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (3, 5, 10000.00, 15.00, 6, 'meses', 6, 2642.45, 5854.70, 15854.70, 15854.70, 0.00, '2026-06-01', '2026-12-01', 'activo', 'Préstamo para equipo de cómputo', 'propio', NULL, NULL, 0.00, 1500.00, '2026-06-01 09:30:00');

-- Préstamo 4: Diego Morales - Cuota fija 24 meses, APALANCADO → Banco Nacional
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (4, 8, 60000.00, 8.00, 24, 'meses', 24, 5699.60, 76790.40, 136790.40, 131090.80, 0.00, '2026-07-01', '2028-07-01', 'activo', 'Crédito Bancor - Inversión en maquinaria', 'apalancado', 1, 5.00, 0.00, 1680.00, '2026-07-01 10:00:00');

-- Préstamo 5: María García - Con abono a capital, 6 meses, propio
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (5, 1, 8000.00, 15.00, 6, 'meses', 6, 2114.01, 4684.06, 12684.06, 7570.05, 0.00, '2026-06-01', '2026-12-01', 'activo', 'Préstamo express con abono a capital', 'propio', NULL, NULL, 3000.00, 1200.00, '2026-06-01 14:00:00');

-- === MOROSOS (3) ===
-- Préstamo 6: Ana Martínez - Vencido hace 22 días
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (6, 3, 20000.00, 15.00, 4, 'meses', 4, 7005.37, 8021.48, 28021.48, 14010.74, 0.00, '2026-03-01', '2026-07-01', 'moroso', 'Solo ha pagado 2 cuotas - No responde llamadas', 'propio', NULL, NULL, 0.00, 3000.00, '2026-03-01 10:30:00');

-- Préstamo 7: Fernando Torres - MUY vencido (53 días)
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (7, 6, 40000.00, 10.00, 6, 'meses', 6, 8184.32, 9105.92, 49105.92, 24552.96, 0.00, '2025-12-01', '2026-06-01', 'moroso', 'Cliente se mudó sin avisar - Contactar garantía', 'propio', NULL, NULL, 0.00, 4000.00, '2025-12-01 11:15:00');

-- Préstamo 8: Carlos Rodríguez - Vencido hace 22 días
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (8, 4, 35000.00, 12.00, 3, 'meses', 3, 14574.70, 8724.10, 43724.10, 29149.40, 0.00, '2026-04-01', '2026-07-01', 'moroso', 'Problema familiar - Solicita espera', 'propio', NULL, NULL, 0.00, 4200.00, '2026-04-01 09:00:00');

-- === PAGADOS (3) ===
-- Préstamo 9: Carlos Rodríguez - Pagado completamente
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (9, 4, 15000.00, 10.00, 3, 'meses', 3, 6031.72, 3095.16, 18095.16, 0.00, 0.00, '2026-01-01', '2026-04-01', 'pagado', 'Préstamo pagado puntualmente', 'propio', NULL, NULL, 0.00, 1500.00, '2026-01-01 10:00:00');

-- Préstamo 10: Patricia Flores - Pagado con puntualidad
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (10, 7, 30000.00, 10.00, 6, 'meses', 6, 6004.50, 6027.00, 36027.00, 0.00, 0.00, '2025-12-01', '2026-06-01', 'pagado', 'Excelente cliente - Siempre a tiempo', 'propio', NULL, NULL, 0.00, 3000.00, '2025-12-01 08:00:00');

-- Préstamo 11: Laura Sánchez - Pagado, primer préstamo
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (11, 5, 20000.00, 12.00, 4, 'meses', 4, 5892.18, 3568.72, 23568.72, 0.00, 0.00, '2026-01-01', '2026-05-01', 'pagado', 'Primer préstamo - Cumplió a tiempo', 'propio', NULL, NULL, 0.00, 2400.00, '2026-01-01 09:45:00');

-- === ACTIVO NUEVO (1) ===
-- Préstamo 12: Miguel Ángel Ríos - Recién dado de julio 2026, apalancado → Financiera del Norte
INSERT INTO prestamos (id, cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, monto_mora_acumulada, fecha_prestamo, fecha_vencimiento, estado, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, abonos_capital, utilidad_mensual, fecha_registro)
VALUES (12, 10, 45000.00, 10.00, 12, 'meses', 12, 6605.26, 34263.12, 79263.12, 79263.12, 0.00, '2026-07-15', '2027-07-15', 'activo', 'Préstamo nuevo - Crédito Comercial Financiera del Norte', 'apalancado', 2, 6.50, 0.00, 1625.00, '2026-07-15 10:00:00');

-- ============================================
-- PAGOS (Historial de todos los préstamos)
-- ============================================

-- Pagos Préstamo 1: María García - 3 cuotas pagadas (May, Jun, Jul 2026)
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(1,  1, 7339.18, 2339.18, 5000.00, 0.00, 0.00, 1, '2026-05-05', '2026-05-05 10:00:00', 80730.98, 'Cuota 1 - Mayo', 0),
(2,  1, 7339.18, 2573.10, 4766.08, 0.00, 0.00, 2, '2026-06-05', '2026-06-05 10:15:00', 73391.80, 'Cuota 2 - Junio', 0),
(3,  1, 7339.18, 2830.41, 4508.77, 0.00, 0.00, 3, '2026-07-03', '2026-07-03 09:30:00', 66052.62, 'Cuota 3 - Julio (adelantada)', 0);

-- Pagos Préstamo 2: Roberto Hernández - 2 cuotas pagadas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(4,  2, 6081.08, 3081.08, 3000.00, 0.00, 0.00, 1, '2026-05-02', '2026-05-02 11:00:00', 30405.40, 'Cuota 1 - Mayo', 0),
(5,  2, 6081.08, 3450.81, 2630.27, 0.00, 0.00, 2, '2026-06-01', '2026-06-01 14:00:00', 24324.32, 'Cuota 2 - Junio', 0);

-- Pagos Préstamo 3: Laura Sánchez - 1 cuota pagada
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(6,  3, 2642.45, 1142.45, 1500.00, 0.00, 0.00, 1, '2026-06-10', '2026-06-10 09:00:00', 13212.25, 'Cuota 1 - Junio', 0);

-- Pagos Préstamo 4: Diego Morales - 1 cuota pagada (apalancado)
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(7,  4, 5699.60, 1699.60, 4000.00, 0.00, 0.00, 1, '2026-07-05', '2026-07-05 10:00:00', 131090.80, 'Cuota 1 - Julio', 0);

-- Pagos Préstamo 5: María García - 1 cuota + 1 abono a capital
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(8,  5, 2114.01, 914.01, 1200.00, 0.00, 0.00, 1, '2026-06-05', '2026-06-05 14:30:00', 10570.05, 'Cuota 1 - Junio', 0),
(9,  5, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0, '2026-06-15', '2026-06-15 11:00:00', 7570.05, 'Abono a capital extra', 1);

-- Pagos Préstamo 6: Ana Martínez (MOROSA) - 2 cuotas pagadas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(10, 6, 7005.37, 4005.37, 3000.00, 0.00, 0.00, 1, '2026-03-05', '2026-03-05 10:00:00', 21016.11, 'Cuota 1 - Marzo', 0),
(11, 6, 7005.37, 4606.18, 2399.19, 0.00, 0.00, 2, '2026-04-03', '2026-04-03 09:00:00', 14010.74, 'Cuota 2 - Abril', 0);

-- Pagos Préstamo 7: Fernando Torres (MUY MOROSO) - 3 cuotas pagadas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(12, 7, 8184.32, 4184.32, 4000.00, 0.00, 0.00, 1, '2025-12-03', '2025-12-03 10:00:00', 40921.60, 'Cuota 1 - Diciembre', 0),
(13, 7, 8184.32, 4602.75, 3581.57, 0.00, 0.00, 2, '2026-01-05', '2026-01-05 11:30:00', 32737.28, 'Cuota 2 - Enero', 0),
(14, 7, 8184.32, 5063.03, 3121.29, 0.00, 0.00, 3, '2026-02-02', '2026-02-02 09:00:00', 24552.96, 'Cuota 3 - Febrero', 0);

-- Pagos Préstamo 8: Carlos Rodríguez (MOROSO) - 1 cuota pagada
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(15, 8, 14574.70, 10374.70, 4200.00, 0.00, 0.00, 1, '2026-04-15', '2026-04-15 09:00:00', 29149.40, 'Cuota 1 - Abril (retrasada)', 0);

-- Pagos Préstamo 9: Carlos Rodríguez (PAGADO) - 3 cuotas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(16, 9, 6031.72, 5031.72, 1000.00, 0.00, 0.00, 1, '2026-01-05', '2026-01-05 10:00:00', 12063.44, 'Cuota 1 - Enero', 0),
(17, 9, 6031.72, 5534.89, 496.83, 0.00, 0.00, 2, '2026-02-04', '2026-02-04 10:00:00', 6031.72, 'Cuota 2 - Febrero', 0),
(18, 9, 6031.72, 6031.72, 0.00, 0.00, 0.00, 3, '2026-03-03', '2026-03-03 10:00:00', 0.00, 'Cuota 3 - Marzo (pago final)', 0);

-- Pagos Préstamo 10: Patricia Flores (PAGADO) - 6 cuotas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(19, 10, 6004.50, 3504.50, 2500.00, 0.00, 0.00, 1, '2025-12-05', '2025-12-05 08:00:00', 30022.50, 'Cuota 1 - Diciembre', 0),
(20, 10, 6004.50, 3854.95, 2149.55, 0.00, 0.00, 2, '2026-01-05', '2026-01-05 08:30:00', 24018.00, 'Cuota 2 - Enero', 0),
(21, 10, 6004.50, 4240.45, 1764.05, 0.00, 0.00, 3, '2026-02-04', '2026-02-04 08:15:00', 18013.50, 'Cuota 3 - Febrero', 0),
(22, 10, 6004.50, 4664.49, 1340.01, 0.00, 0.00, 4, '2026-03-05', '2026-03-05 09:00:00', 12009.00, 'Cuota 4 - Marzo', 0),
(23, 10, 6004.50, 5130.94, 873.56, 0.00, 0.00, 5, '2026-04-03', '2026-04-03 08:00:00', 6004.50, 'Cuota 5 - Abril', 0),
(24, 10, 6004.50, 5643.54, 360.96, 0.00, 0.00, 6, '2026-05-05', '2026-05-05 08:00:00', 0.00, 'Cuota 6 - Mayo (pago final)', 0);

-- Pagos Préstamo 11: Laura Sánchez (PAGADO) - 4 cuotas
INSERT INTO pagos (id, prestamo_id, monto_pagado, capital_pagado, interes_pagado, mora_pagada, descuento, numero_cuota, fecha_pago, fecha_registro, saldo_despues_pago, observaciones, es_abono_capital) VALUES
(25, 11, 5892.18, 3892.18, 2000.00, 0.00, 0.00, 1, '2026-01-05', '2026-01-05 09:45:00', 17676.54, 'Cuota 1 - Enero', 0),
(26, 11, 5892.18, 4281.40, 1610.78, 0.00, 0.00, 2, '2026-02-05', '2026-02-05 10:00:00', 11784.36, 'Cuota 2 - Febrero', 0),
(27, 11, 5892.18, 4709.54, 1182.64, 0.00, 0.00, 3, '2026-03-04', '2026-03-04 09:30:00', 5892.18, 'Cuota 3 - Marzo', 0),
(28, 11, 5892.18, 5892.18, 0.00, 0.00, 0.00, 4, '2026-04-02', '2026-04-02 10:00:00', 0.00, 'Cuota 4 - Abril (pago final)', 0);

-- Pagos Préstamo 12: Miguel Ángel Ríos - Sin pagos aún (recién julio 2026)

-- ============================================
-- CONFIGURACIÓN
-- ============================================
UPDATE configuracion SET
    nombre_empresa = 'Préstamos MX - Grupo Financiero',
    tasa_mora_diaria = 1.00,
    dias_gracia = 3,
    dias_gracia_interes = 3,
    moneda = 'MXN'
WHERE id = 1;

-- ============================================
-- MIS DEUDAS (4 deudas que tengo yo)
-- ============================================
INSERT INTO mis_deudas (id, acreedor_id, concepto, fecha_inicio, fecha_vencimiento, monto_recibido, tasa_mensual, abonos_capital_pagados, capital_vigente, interes_mensual, notas, fecha_registro, estado) VALUES
(1, 1, 'Línea de crédito Bancor - Capital de trabajo', '2026-01-01', NULL, 100000.00, 8.00, 0.00, 100000.00, 8000.00, 'Línea revolvente - Pago mínimo mensual de intereses', '2026-01-01 10:00:00', 'activo'),
(2, 2, 'Préstamo Financiera del Norte - Inversión equipo', '2026-04-01', '2027-04-01', 50000.00, 12.00, 5000.00, 45000.00, 5400.00, 'Aboné 5,000 a capital en junio', '2026-04-01 11:00:00', 'activo'),
(3, 3, 'Préstamo personal Juan Pérez', '2026-05-01', '2026-11-01', 20000.00, 15.00, 0.00, 20000.00, 3000.00, 'Préstamo de confianza - Amigo', '2026-05-01 09:30:00', 'activo'),
(4, 4, 'Institución Grupo Financiero MX - Crédito puente', '2025-10-01', '2026-10-01', 200000.00, 6.00, 20000.00, 180000.00, 10800.00, 'Crédito puente para compra de inmueble', '2025-10-01 11:00:00', 'activo');

-- ============================================
-- PAGOS DEUDAS (Historial de pagos a acreedores)
-- ============================================

-- Pagos Deuda 1: Banco Nacional - 7 pagos de intereses mensuales
INSERT INTO pagos_deudas (id, deuda_id, monto_pagado, capital_pagado, interes_pagado, fecha_pago, fecha_registro, notas) VALUES
(1,  1, 8000.00,  0.00, 8000.00, '2026-02-01', '2026-02-01 10:00:00', 'Pago interés enero'),
(2,  1, 8000.00,  0.00, 8000.00, '2026-03-01', '2026-03-01 10:00:00', 'Pago interés febrero'),
(3,  1, 8000.00,  0.00, 8000.00, '2026-04-01', '2026-04-01 10:00:00', 'Pago interés marzo'),
(4,  1, 8000.00,  0.00, 8000.00, '2026-05-01', '2026-05-01 10:00:00', 'Pago interés abril'),
(5,  1, 8000.00,  0.00, 8000.00, '2026-06-01', '2026-06-01 10:00:00', 'Pago interés mayo'),
(6,  1, 8000.00,  0.00, 8000.00, '2026-07-01', '2026-07-01 10:00:00', 'Pago interés junio'),
(7,  1, 8000.00,  0.00, 8000.00, '2026-07-15', '2026-07-15 10:00:00', 'Pago interés julio (anticipado)');

-- Pagos Deuda 2: Financiera del Norte - 3 pagos + abono a capital
INSERT INTO pagos_deudas (id, deuda_id, monto_pagado, capital_pagado, interes_pagado, fecha_pago, fecha_registro, notas) VALUES
(8,  2, 5400.00,  0.00, 5400.00, '2026-05-01', '2026-05-01 11:00:00', 'Pago interés abril'),
(9,  2, 10400.00, 5000.00, 5400.00, '2026-06-01', '2026-06-01 11:00:00', 'Pago interés mayo + abono capital'),
(10, 2, 5400.00,  0.00, 5400.00, '2026-07-01', '2026-07-01 11:00:00', 'Pago interés junio');

-- Pagos Deuda 3: Juan Pérez - 2 pagos
INSERT INTO pagos_deudas (id, deuda_id, monto_pagado, capital_pagado, interes_pagado, fecha_pago, fecha_registro, notas) VALUES
(11, 3, 3000.00,  0.00, 3000.00, '2026-06-01', '2026-06-01 09:30:00', 'Pago interés mayo'),
(12, 3, 3000.00,  0.00, 3000.00, '2026-07-01', '2026-07-01 09:30:00', 'Pago interés junio');

-- Pagos Deuda 4: Grupo Financiero MX - 9 pagos + abonos capital
INSERT INTO pagos_deudas (id, deuda_id, monto_pagado, capital_pagado, interes_pagado, fecha_pago, fecha_registro, notas) VALUES
(13, 4, 10800.00,  0.00, 10800.00, '2025-11-01', '2025-11-01 11:00:00', 'Pago interés octubre'),
(14, 4, 10800.00,  0.00, 10800.00, '2025-12-01', '2025-12-01 11:00:00', 'Pago interés noviembre'),
(15, 4, 30800.00, 20000.00, 10800.00, '2026-01-02', '2026-01-02 11:00:00', 'Pago diciembre + abono capital Q1'),
(16, 4, 10800.00,  0.00, 10800.00, '2026-02-02', '2026-02-02 11:00:00', 'Pago interés enero'),
(17, 4, 10800.00,  0.00, 10800.00, '2026-03-03', '2026-03-03 11:00:00', 'Pago interés febrero'),
(18, 4, 10800.00,  0.00, 10800.00, '2026-04-01', '2026-04-01 11:00:00', 'Pago interés marzo'),
(19, 4, 10800.00,  0.00, 10800.00, '2026-05-01', '2026-05-01 11:00:00', 'Pago interés abril'),
(20, 4, 10800.00,  0.00, 10800.00, '2026-06-01', '2026-06-01 11:00:00', 'Pago interés mayo'),
(21, 4, 10800.00,  0.00, 10800.00, '2026-07-01', '2026-07-01 11:00:00', 'Pago interés junio');

-- ============================================
-- AUTO_INCREMENT RESET
-- ============================================
ALTER TABLE clientes AUTO_INCREMENT = 11;
ALTER TABLE prestamos AUTO_INCREMENT = 13;
ALTER TABLE pagos AUTO_INCREMENT = 29;
ALTER TABLE acreedores AUTO_INCREMENT = 5;
ALTER TABLE mis_deudas AUTO_INCREMENT = 5;
ALTER TABLE pagos_deudas AUTO_INCREMENT = 22;

-- ============================================
-- VERIFICACIÓN
-- ============================================
SELECT '=== CLIENTES ===' AS tabla, COUNT(*) AS total FROM clientes WHERE estado = 'activo'
UNION ALL
SELECT '=== ACREEDORES ===', COUNT(*) FROM acreedores WHERE estado = 'activo'
UNION ALL
SELECT '=== PRÉSTAMOS ACTIVOS ===', COUNT(*) FROM prestamos WHERE estado = 'activo'
UNION ALL
SELECT '=== PRÉSTAMOS MOROSOS ===', COUNT(*) FROM prestamos WHERE estado = 'moroso'
UNION ALL
SELECT '=== PRÉSTAMOS PAGADOS ===', COUNT(*) FROM prestamos WHERE estado = 'pagado'
UNION ALL
SELECT '=== PAGOS TOTALES ===', COUNT(*) FROM pagos
UNION ALL
SELECT '=== MIS DEUDAS ===', COUNT(*) FROM mis_deudas WHERE estado = 'activo'
UNION ALL
SELECT '=== PAGOS DEUDAS ===', COUNT(*) FROM pagos_deudas;

SELECT 'RESUMEN FINANCIERO:' AS info;
SELECT
    CONCAT('Total prestado: MXN ', FORMAT(SUM(monto_prestado), 2)) AS activos FROM prestamos WHERE estado IN ('activo','moroso')
UNION ALL
SELECT CONCAT('Total cobrado: MXN ', FORMAT(SUM(monto_pagado), 2)) FROM pagos
UNION ALL
SELECT CONCAT('Total que debo: MXN ', FORMAT(SUM(capital_vigente), 2)) FROM mis_deudas WHERE estado = 'activo'
UNION ALL
SELECT CONCAT('Intereses a cobrar/mes: MXN ', FORMAT(
    (SELECT COALESCE(SUM((p.monto_prestado - p.abonos_capital) * p.tasa_interes / 100), 0)
     FROM prestamos p WHERE p.estado IN ('activo','moroso')), 2))
UNION ALL
SELECT CONCAT('Intereses a pagar/mes: MXN ', FORMAT(
    (SELECT COALESCE(SUM(d.capital_vigente * d.tasa_mensual / 100), 0)
     FROM mis_deudas d WHERE d.estado = 'activo'), 2));

SELECT '=== MOROSOS ===' AS reporte;
SELECT
    CONCAT(c.nombre, ' ', c.apellido) AS cliente,
    CONCAT('MXN ', FORMAT(p.saldo_pendiente, 2)) AS saldo,
    DATEDIFF(CURDATE(), p.fecha_vencimiento) AS dias_atraso,
    CONCAT('$', FORMAT(p.saldo_pendiente * 0.01 * GREATEST(DATEDIFF(CURDATE(), p.fecha_vencimiento) - 3, 0), 2)) AS mora_estimada
FROM prestamos p
INNER JOIN clientes c ON p.cliente_id = c.id
WHERE p.estado = 'moroso'
ORDER BY dias_atraso DESC;

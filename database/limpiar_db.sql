-- ============================================
-- LIMPIEZA DE BD PARA PRUEBAS DE PRESTAMOS
-- Conserva: usuarios, configuracion, clientes
-- Limpia: prestamos, pagos, acreedores, mis_deudas, pagos_deudas
-- ============================================

USE prestamos_db;

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar historial de pagos
TRUNCATE TABLE pagos_deudas;
TRUNCATE TABLE pagos;

-- Limpiar prestamos
TRUNCATE TABLE prestamos;

-- Limpiar deudas propias
TRUNCATE TABLE mis_deudas;

-- Limpiar acreedores
TRUNCATE TABLE acreedores;

-- Limpiar clientes (se reinsertan abajo)
TRUNCATE TABLE clientes;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- CLIENTES DE PRUEBA (10 clientes variados)
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
-- ACREEDORES DE PRUEBA (para testing apalancado)
-- ============================================
INSERT INTO acreedores (id, nombre, telefono, email, rfc, ciudad, notas, fecha_registro, estado) VALUES
(1, 'Banco Nacional S.A.', '5551112233', 'creditos@bancona.com', 'BNA850101XXX', 'Ciudad de México', 'Banco principal - Línea de crédito aprobada', '2025-06-01 10:00:00', 'activo'),
(2, 'Financiera del Norte S.A.', '5552223344', 'prestamos@findelno.com', 'FNO920515XXX', 'Monterrey', 'Financiera para crédito comercial', '2025-09-15 14:00:00', 'activo'),
(3, 'Juan Pérez Martínez', '5553334455', 'juan.perez@gmail.com', NULL, 'Guadalajara', 'Préstamo personal - Amigo de confianza', '2026-01-05 09:30:00', 'activo'),
(4, 'Grupo Financiero MX S.C.', '5554445566', 'inversiones@gfmx.com', 'GFM030320XXX', 'Puebla', 'Institucional - Fondos de inversión', '2025-11-01 11:00:00', 'activo');

-- ============================================
-- CONFIGURACION (mantener limpio)
-- ============================================
UPDATE configuracion SET
    nombre_empresa = 'Préstamos MX - Grupo Financiero',
    tasa_mora_diaria = 1.00,
    dias_gracia = 3,
    dias_gracia_interes = 3,
    moneda = 'MXN',
    capital_propio_inicial = 0,
    preguntar_capital_inicial = 1
WHERE id = 1;

-- ============================================
-- RESET AUTO_INCREMENT
-- ============================================
ALTER TABLE clientes AUTO_INCREMENT = 11;
ALTER TABLE prestamos AUTO_INCREMENT = 1;
ALTER TABLE pagos AUTO_INCREMENT = 1;
ALTER TABLE acreedores AUTO_INCREMENT = 5;
ALTER TABLE mis_deudas AUTO_INCREMENT = 1;
ALTER TABLE pagos_deudas AUTO_INCREMENT = 1;

-- ============================================
-- VERIFICACION
-- ============================================
SELECT '=== LIMPIEZA COMPLETADA ===' AS resultado;

SELECT 'USUARIOS (conservados)' AS tabla, COUNT(*) AS total FROM usuarios
UNION ALL
SELECT 'CLIENTES (de prueba)', COUNT(*) FROM clientes WHERE estado = 'activo'
UNION ALL
SELECT 'ACREEDORES (de prueba)', COUNT(*) FROM acreedores WHERE estado = 'activo'
UNION ALL
SELECT 'PRESTAMOS', COUNT(*) FROM prestamos
UNION ALL
SELECT 'PAGOS', COUNT(*) FROM pagos
UNION ALL
SELECT 'MIS DEUDAS', COUNT(*) FROM mis_deudas
UNION ALL
SELECT 'PAGOS DEUDAS', COUNT(*) FROM pagos_deudas;

SELECT 'Puedes crear prestamos desde la interfaz con los clientes listos.' AS nota;

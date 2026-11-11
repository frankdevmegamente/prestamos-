-- ============================================
-- MIGRACION: SISTEMA HIBRIDO DE PRESTAMOS
-- Agrega acreedores, mis deudas, fuente de fondos
-- y mejoras al modelo existente
-- ============================================

USE prestamos_db;

-- 1. Nueva tabla: ACREEDORES (quienes me prestan dinero)
CREATE TABLE IF NOT EXISTS acreedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    rfc VARCHAR(20) DEFAULT NULL,
    ciudad VARCHAR(100) DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB;

-- 2. Nueva tabla: MIS DEUDAS (dinero que yo debo a acreedores)
CREATE TABLE IF NOT EXISTS mis_deudas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acreedor_id INT NOT NULL,
    concepto VARCHAR(200) DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    monto_recibido DECIMAL(12,2) NOT NULL,
    tasa_mensual DECIMAL(5,2) NOT NULL COMMENT 'Tasa mensual que pago',
    abonos_capital_pagados DECIMAL(12,2) DEFAULT 0.00,
    capital_vigente DECIMAL(12,2) NOT NULL,
    interes_mensual DECIMAL(12,2) NOT NULL,
    notas TEXT DEFAULT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo','pagado') DEFAULT 'activo',
    FOREIGN KEY (acreedor_id) REFERENCES acreedores(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. Nueva tabla: PAGOS DEUDAS (historial de pagos a acreedores)
CREATE TABLE IF NOT EXISTS pagos_deudas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deuda_id INT NOT NULL,
    monto_pagado DECIMAL(12,2) NOT NULL,
    capital_pagado DECIMAL(12,2) DEFAULT 0.00,
    interes_pagado DECIMAL(12,2) DEFAULT 0.00,
    fecha_pago DATE NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    notas TEXT DEFAULT NULL,
    FOREIGN KEY (deuda_id) REFERENCES mis_deudas(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. Agregar columnas a PRESTAMOS
ALTER TABLE prestamos ADD COLUMN fuente_fondos ENUM('propio','apalancado') DEFAULT 'propio' AFTER observaciones;
ALTER TABLE prestamos ADD COLUMN acreedor_id INT DEFAULT NULL AFTER fuente_fondos;
ALTER TABLE prestamos ADD COLUMN tasa_acreedor DECIMAL(5,2) DEFAULT NULL AFTER acreedor_id;
ALTER TABLE prestamos ADD COLUMN abonos_capital DECIMAL(12,2) DEFAULT 0.00 AFTER tasa_acreedor;
ALTER TABLE prestamos ADD COLUMN utilidad_mensual DECIMAL(12,2) DEFAULT 0.00 AFTER abonos_capital;

-- 5. Agregar columna a PAGOS
ALTER TABLE pagos ADD COLUMN es_abono_capital TINYINT(1) DEFAULT 0 AFTER observaciones;

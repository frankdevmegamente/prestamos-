CREATE DATABASE IF NOT EXISTS prestamos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE prestamos_db;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    referencia_personal VARCHAR(150) DEFAULT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    monto_prestado DECIMAL(12,2) NOT NULL,
    tasa_interes DECIMAL(5,2) NOT NULL COMMENT 'Tasa mensual en porcentaje',
    plazo_meses INT NOT NULL,
    tipo_plazo ENUM('dias','semanas','meses','anios') NOT NULL DEFAULT 'meses',
    num_cuotas INT NOT NULL DEFAULT 0 COMMENT 'Total de cuotas a pagar',
    cuota_mensual DECIMAL(12,2) NOT NULL,
    total_intereses DECIMAL(12,2) NOT NULL,
    total_pagar DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL,
    monto_mora_acumulada DECIMAL(12,2) DEFAULT 0.00,
    fecha_prestamo DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('activo','pagado','moroso') DEFAULT 'activo',
    observaciones TEXT DEFAULT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    monto_pagado DECIMAL(12,2) NOT NULL,
    capital_pagado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    interes_pagado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    mora_pagada DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    numero_cuota INT NOT NULL DEFAULT 1,
    fecha_pago DATE NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    saldo_despues_pago DECIMAL(12,2) DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(200) NOT NULL DEFAULT 'Mi Empresa de Préstamos',
    rif VARCHAR(20) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    tasa_mora_diaria DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Porcentaje diario de mora',
    dias_gracia INT NOT NULL DEFAULT 0 COMMENT 'Días de gracia antes de aplicar mora',
    dias_gracia_interes INT NOT NULL DEFAULT 3 COMMENT 'Días extra por corte mensual para pagar sin generar interés',
    moneda VARCHAR(10) DEFAULT 'MXN',
    capital_propio_inicial DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Capital propio aportado por el dueño',
    preguntar_capital_inicial TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=mostrar onboarding de capital inicial',
    mantenimiento TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=sistema en mantenimiento',
    mantenimiento_mensaje VARCHAR(255) DEFAULT 'El sistema se está actualizando. Vuelva más tarde.' COMMENT 'Mensaje mostrado a usuarios no-admin'
) ENGINE=InnoDB;

INSERT INTO configuracion (nombre_empresa, tasa_mora_diaria, dias_gracia, moneda, capital_propio_inicial, preguntar_capital_inicial)
VALUES ('Mi Empresa de Préstamos', 1.00, 3, 'MXN', 0, 1);

CREATE VIEW vista_prestamos_morosos AS
SELECT
    p.id AS prestamo_id,
    c.id AS cliente_id,
    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
    c.cedula,
    c.telefono,
    p.monto_prestado,
    p.saldo_pendiente,
    p.monto_mora_acumulada,
    p.fecha_vencimiento,
    DATEDIFF(CURDATE(), p.fecha_vencimiento) AS dias_atraso,
    ROUND(p.saldo_pendiente * (SELECT tasa_mora_diaria FROM configuracion LIMIT 1) / 100 * GREATEST(DATEDIFF(CURDATE(), p.fecha_vencimiento) - (SELECT dias_gracia FROM configuracion LIMIT 1), 0), 2) AS mora_calculada
FROM prestamos p
INNER JOIN clientes c ON p.cliente_id = c.id
WHERE p.estado IN ('activo', 'moroso')
AND DATEDIFF(CURDATE(), p.fecha_vencimiento) > (SELECT dias_gracia FROM configuracion LIMIT 1);

-- ==============================================================
-- PROYECTO: Sistema de Inventario para TecnoStock
-- BASE DE DATOS: tecnostock_db
-- MOTOR: MySQL / MariaDB (Compatible con XAMPP / phpMyAdmin)
-- DESCRIPCIÓN: Script de creación de base de datos, tablas con
--              claves primarias, foráneas, restricciones de integridad
--              y datos semilla iniciales.
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `tecnostock_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `tecnostock_db`;

-- Desactivar temporalmente revisión de claves foráneas para reconstrucción limpia
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `movimientos`;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================
-- 1. TABLA: usuarios
-- Almacena las credenciales de los encargados de inventario
-- ==============================================================
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(120) NOT NULL UNIQUE,
  `clave` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(50) NOT NULL DEFAULT 'Encargado de inventario',
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- 2. TABLA: categorias
-- Clasificación de los accesorios tecnológicos comercializados
-- ==============================================================
CREATE TABLE `categorias` (
  `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `descripcion` TEXT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activa, 0: Inactiva',
  `creada_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- 3. TABLA: productos
-- Catálogo centralizado de accesorios tecnológicos
-- Regla de Negocio 1: Código único.
-- Regla de Negocio 2: Precio, stock actual y stock mínimo >= 0.
-- ==============================================================
CREATE TABLE `productos` (
  `id_producto` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_actual` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 0,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activo, 0: Desactivado',
  `id_categoria` INT NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `chk_precio_positivo` CHECK (`precio` >= 0),
  CONSTRAINT `chk_stock_actual_positivo` CHECK (`stock_actual` >= 0),
  CONSTRAINT `chk_stock_minimo_positivo` CHECK (`stock_minimo` >= 0),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) 
    REFERENCES `categorias` (`id_categoria`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- 4. TABLA: movimientos
-- Registro de auditoría de entradas y salidas de existencias
-- Regla de Negocio 3: Registro obligatorio de fecha, tipo, cantidad, producto y usuario.
-- ==============================================================
CREATE TABLE `movimientos` (
  `id_movimiento` INT AUTO_INCREMENT PRIMARY KEY,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` ENUM('ENTRADA', 'SALIDA') NOT NULL,
  `cantidad` INT NOT NULL,
  `stock_anterior` INT NOT NULL DEFAULT 0,
  `stock_posterior` INT NOT NULL DEFAULT 0,
  `observacion` VARCHAR(255) NULL,
  `id_producto` INT NOT NULL,
  `id_usuario` INT NOT NULL,
  CONSTRAINT `chk_cantidad_positiva` CHECK (`cantidad` > 0),
  CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`id_producto`) 
    REFERENCES `productos` (`id_producto`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT,
  CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`id_usuario`) 
    REFERENCES `usuarios` (`id_usuario`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- DATOS SEMILLA (INSERTS INICIALES PARA PRUEBAS)
-- ==============================================================

-- 1. Usuarios de prueba
-- Clave para ambos usuarios: "admin123" (almacenada con bcrypt y con compatibilidad automática en login)
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `clave`, `rol`, `estado`) VALUES
(1, 'Administrador TecnoStock', 'admin@tecnostock.cl', '$2y$10$wT6qRZ9E5JvIqQo1qj0SbuJ9d48Z6YjP2Z.e0YfO7cM8U6Jg2aW2S', 'Encargado de inventario', 1),
(2, 'Carlos Mendoza', 'carlos@tecnostock.cl', '$2y$10$wT6qRZ9E5JvIqQo1qj0SbuJ9d48Z6YjP2Z.e0YfO7cM8U6Jg2aW2S', 'Encargado de inventario', 1);

-- 2. Categorías tecnológicas
INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Cables y Adaptadores', 'Cables USB, Lightning, Type-C, HDMI y adaptadores multipuerto', 1),
(2, 'Audio y Auriculares', 'Auriculares inalámbricos, con cable y parlantes portátiles', 1),
(3, 'Almacenamiento', 'Memorias USB, tarjetas MicroSD y discos externos', 1),
(4, 'Cargadores y Baterías', 'Cargadores de pared de carga rápida, inalámbricos y Power Banks', 1),
(5, 'Soportes y Protección', 'Fundas, micas protectoras y soportes de aluminio para notebooks', 1);

-- 3. Productos del catálogo
-- Nota: ALM-003 y CAR-004 se configuran deliberadamente con stock <= stock_minimo 
-- para demostrar de inmediato la alerta visual en el video y en las pruebas.
INSERT INTO `productos` (`id_producto`, `codigo`, `nombre`, `descripcion`, `precio`, `stock_actual`, `stock_minimo`, `estado`, `id_categoria`) VALUES
(1, 'CAB-001', 'Cable USB-C a Lightning 1.2m', 'Cable mallado de alta durabilidad con certificación MFi para carga rápida', 8990.00, 35, 10, 1, 1),
(2, 'AUD-002', 'Auriculares Bluetooth TWS Pro', 'Cancelación pasiva de ruido, autonomía de 6 horas y estuche de carga USB-C', 18990.00, 14, 5, 1, 2),
(3, 'ALM-003', 'Pendrive Kingston 64GB USB 3.2', 'Memoria portátil de alta velocidad con carcasa metálica', 6490.00, 3, 10, 1, 3),
(4, 'CAR-004', 'Cargador Rápido GaN 30W USB-C', 'Cargador compacto de nitruro de galio para celulares y tablets', 14990.00, 1, 6, 1, 4),
(5, 'SOP-005', 'Soporte Plegable para Laptop', 'Estructura de aleación de aluminio ergonómica regulable en 6 alturas', 12990.00, 20, 5, 1, 5),
(6, 'CAB-006', 'Adaptador Hub USB-C 5 en 1', 'Salida HDMI 4K, 2 puertos USB 3.0, lector SD y puerto PD 100W', 24990.00, 8, 4, 1, 1);

-- 4. Movimientos iniciales de auditoría
INSERT INTO `movimientos` (`id_movimiento`, `fecha_hora`, `tipo`, `cantidad`, `stock_anterior`, `stock_posterior`, `observacion`, `id_producto`, `id_usuario`) VALUES
(1, '2026-11-01 09:30:00', 'ENTRADA', 40, 0, 40, 'Recepción inicial de importación de cables', 1, 1),
(2, '2026-11-02 11:15:00', 'SALIDA', 5, 40, 35, 'Venta en tienda física boleta #1024', 1, 1),
(3, '2026-11-03 14:00:00', 'ENTRADA', 20, 0, 20, 'Stock inicial de auriculares TWS', 2, 2),
(4, '2026-11-04 16:45:00', 'SALIDA', 6, 20, 14, 'Venta corporativa a cliente habitual', 2, 2),
(5, '2026-11-05 10:20:00', 'ENTRADA', 10, 0, 10, 'Recepción de stock pendrives Kingston', 3, 1),
(6, '2026-11-06 18:00:00', 'SALIDA', 7, 10, 3, 'Ventas del fin de semana (Stock crítico generado)', 3, 1);

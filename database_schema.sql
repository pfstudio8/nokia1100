-- Script de Creación de Base de Datos para NOKIA1100
-- Preparado para ejecutar directamente en MySQL Workbench

DROP DATABASE IF EXISTS nokia1100;
CREATE DATABASE nokia1100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nokia1100;

-- --------------------------------------------------------
-- Limpiar tablas si existen (orden inverso para no romper FKs)
-- --------------------------------------------------------
DROP TABLE IF EXISTS producto_compra_historial;
DROP TABLE IF EXISTS detalle_compra;
DROP TABLE IF EXISTS compra;
DROP TABLE IF EXISTS proveedor;
DROP TABLE IF EXISTS detalle_venta;
DROP TABLE IF EXISTS venta;
DROP TABLE IF EXISTS inventario;
DROP TABLE IF EXISTS producto_detalle;
DROP TABLE IF EXISTS producto;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS persona;

-- --------------------------------------------------------
-- 1. TABLA PERSONA
-- --------------------------------------------------------
CREATE TABLE persona (
    id_persona INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    direccion VARCHAR(255)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. TABLA USUARIO
-- --------------------------------------------------------
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT NOT NULL,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL, 
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. TABLA PROVEEDOR
-- --------------------------------------------------------
CREATE TABLE proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    domicilio VARCHAR(255),
    telefono VARCHAR(20),
    atencion VARCHAR(100), -- Nombre de la persona de contacto
    email VARCHAR(150)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. TABLA PRODUCTO
-- --------------------------------------------------------
CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 5. TABLA PRODUCTO_DETALLE
-- --------------------------------------------------------
CREATE TABLE producto_detalle (
    id_producto INT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 6. TABLA INVENTARIO
-- --------------------------------------------------------
CREATE TABLE inventario (
    id_producto INT PRIMARY KEY,
    cantidad INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 7. TABLA VENTA
-- --------------------------------------------------------
CREATE TABLE venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    metodo_de_pago VARCHAR(50)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 8. TABLA DETALLE_VENTA
-- --------------------------------------------------------
CREATE TABLE detalle_venta (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    nombre_producto VARCHAR(150) NOT NULL,
    precio_copiado DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 9. TABLA COMPRA
-- --------------------------------------------------------
CREATE TABLE compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    fecha DATETIME NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    descripcion TEXT,
    tiempo_entrega VARCHAR(100),
    iva DECIMAL(5, 2) DEFAULT 0.00,
    autorizado_por VARCHAR(100),
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 10. TABLA DETALLE_COMPRA
-- --------------------------------------------------------
CREATE TABLE detalle_compra (
    id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_compra DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_compra) REFERENCES compra(id_compra) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 11. TABLA PRODUCTO_COMPRA_HISTORIAL
-- --------------------------------------------------------
CREATE TABLE producto_compra_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_detalle_compra INT NOT NULL,
    nombre_producto VARCHAR(150),
    marca VARCHAR(100),
    modelo VARCHAR(100),
    FOREIGN KEY (id_detalle_compra) REFERENCES detalle_compra(id_detalle_compra) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- DATOS DE PRUEBA INICIALES (OPCIONAL)
-- --------------------------------------------------------

-- Insertar usuario admin por defecto (Contraseña: admin123)
-- Nota: La contraseña está hasheada usando BCRYPT que es el estándar de PHP password_hash()
INSERT INTO persona (nombre, apellido, dni, telefono, email, direccion) 
VALUES ('Administrador', 'Sistema', '11111111', '0000000000', 'admin@nokia1100.com', 'Local Central');

INSERT INTO usuario (id_persona, nombre_usuario, contrasena, rol) 
VALUES (1, 'admin', '$2y$10$O4b.9D0m/T68.D0S.JIFaO/vA2gI4n1wG.L7H.490O19Y1R67kHGW', 'admin');

-- Insertar un producto de prueba
INSERT INTO producto (nombre, precio, is_active) VALUES ('Nokia 1100 Original', 50000.00, 1);
INSERT INTO producto_detalle (id_producto, marca, modelo) VALUES (1, 'Nokia', '1100');
INSERT INTO inventario (id_producto, cantidad) VALUES (1, 15);

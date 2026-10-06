-- Base de datos: Unión del Sur
CREATE DATABASE IF NOT EXISTS union_del_sur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE union_del_sur;

-- Usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administracion', 'Entrenador', 'Familia') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Categorías del club
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    dt_nombre VARCHAR(100),
    cuota_mensual DECIMAL(10,2) NOT NULL DEFAULT 0,
    cantidad_jugadores INT NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Datos de prueba
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin Principal', 'admin@uniondelsur.com', '$2a$11$K5oHXGNPc3TQMiKzY3hJveQXLfR3Nlo5hZYEq9CrNUK9TXcBMHVrW', 'Administracion'),
('Lucas Entrenador', 'lucas@uniondelsur.com', '$2a$11$K5oHXGNPc3TQMiKzY3hJveQXLfR3Nlo5hZYEq9CrNUK9TXcBMHVrW', 'Entrenador'),
('Juan Familia', 'juan@mail.com', '$2a$11$K5oHXGNPc3TQMiKzY3hJveQXLfR3Nlo5hZYEq9CrNUK9TXcBMHVrW', 'Familia');
-- Contraseña para todos: admin123

INSERT INTO categorias (nombre, dt_nombre, cuota_mensual, cantidad_jugadores) VALUES
('Sub-8',   'Ramiro Acosta',  12000, 18),
('Sub-10',  'Carlos Benítez', 15000, 22),
('Sub-12',  'Carlos Benítez', 18000, 24),
('Sub-14',  'Diego Sosa',     18000, 20),
('Sub-16',  'Diego Sosa',     20000, 19),
('Primera', 'Martín Romero',  22000, 25);

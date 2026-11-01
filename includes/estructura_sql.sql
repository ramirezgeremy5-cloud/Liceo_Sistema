-- Ejecuta este código en phpMyAdmin

-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_liceo;
USE sistema_liceo;

-- 2. Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE,
    nombre VARCHAR(100),
    rol VARCHAR(50)
);

-- 3. Crear tabla de asignaciones (Conecta al profesor con la sección)
CREATE TABLE IF NOT EXISTS asignaciones_docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_profesor VARCHAR(50),
    seccion VARCHAR(100)
);

-- 4. Crear tabla de estudiantes (Para pasar asistencia real)
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE,
    nombres VARCHAR(100),
    apellidos VARCHAR(100),
    seccion VARCHAR(100)
);

-- 5. Insertar datos iniciales de prueba (Opcional, pero recomendado para probar)
INSERT IGNORE INTO usuarios (usuario, nombre, rol) VALUES 
('jose_paez', 'Jose Paez', 'Docente'),
('prof_demo', 'Profesor Demo', 'Docente'),
('admin', 'Coordinador Principal', 'Coordinador');

INSERT IGNORE INTO estudiantes (cedula, nombres, apellidos, seccion) VALUES
('30.123.456', 'Juan', 'Pérez', '5to Año A - Informática'),
('30.123.457', 'María', 'Gómez', '5to Año A - Informática'),
('31.123.456', 'Ana', 'López', '4to Año B - Contabilidad'),
('32.987.654', 'Carlos', 'Ruiz', '4to Año C - Administración');
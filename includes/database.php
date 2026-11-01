<?php
/**
 * Configuración de Base de Datos SQLite
 * Escuela Técnica Pedro Garcia Leal
 */

// Ruta de la base de datos
$db_path = __DIR__ . '/../datos/sistema.db';

try {
    // Conectar a la base de datos SQLite
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tablas si no existen
    $db->exec("
        CREATE TABLE IF NOT EXISTS estudiantes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cedula TEXT UNIQUE NOT NULL,
            nombre TEXT NOT NULL,
            apellido TEXT NOT NULL,
            fecha_nacimiento DATE,
            genero TEXT,
            nivel_academico TEXT,
            telefono TEXT,
            email TEXT,
            direccion TEXT,
            ciudad TEXT,
            estado_civil TEXT,
            seccion TEXT,
            fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
            estado TEXT DEFAULT 'Activo'
        );
        
        CREATE TABLE IF NOT EXISTS representantes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cedula TEXT UNIQUE NOT NULL,
            nombre TEXT NOT NULL,
            apellido TEXT NOT NULL,
            parentesco TEXT,
            telefono TEXT,
            email TEXT,
            direccion TEXT,
            ciudad TEXT,
            ocupacion TEXT,
            estado TEXT DEFAULT 'Activo'
        );
        
        CREATE TABLE IF NOT EXISTS estudiante_representante (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            estudiante_id INTEGER NOT NULL,
            representante_id INTEGER NOT NULL,
            tipo_responsabilidad TEXT,
            FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
            FOREIGN KEY (representante_id) REFERENCES representantes(id)
        );
    ");
    
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Funciones para operaciones con estudiantes
function obtenerEstudiantes($db) {
    $sql = "SELECT * FROM estudiantes ORDER BY nombre ASC";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEstudiantePorId($db, $id) {
    $sql = "SELECT * FROM estudiantes WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerEstudiantePorCedula($db, $cedula) {
    $sql = "SELECT * FROM estudiantes WHERE cedula = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$cedula]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function agregarEstudiante($db, $datos) {
    $sql = "INSERT INTO estudiantes (cedula, nombre, apellido, fecha_nacimiento, genero, 
            nivel_academico, telefono, email, direccion, ciudad, estado_civil, seccion, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $datos['cedula'],
        $datos['nombre'],
        $datos['apellido'],
        $datos['fecha_nacimiento'] ?? null,
        $datos['genero'] ?? null,
        $datos['nivel_academico'] ?? null,
        $datos['telefono'] ?? null,
        $datos['email'] ?? null,
        $datos['direccion'] ?? null,
        $datos['ciudad'] ?? null,
        $datos['estado_civil'] ?? null,
        $datos['seccion'] ?? null,
        $datos['estado'] ?? 'Activo'
    ]);
}

function actualizarEstudiante($db, $id, $datos) {
    $sql = "UPDATE estudiantes SET 
            cedula = ?, nombre = ?, apellido = ?, fecha_nacimiento = ?, 
            genero = ?, nivel_academico = ?, telefono = ?, email = ?, 
            direccion = ?, ciudad = ?, estado_civil = ?, seccion = ?, estado = ?
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $datos['cedula'],
        $datos['nombre'],
        $datos['apellido'],
        $datos['fecha_nacimiento'] ?? null,
        $datos['genero'] ?? null,
        $datos['nivel_academico'] ?? null,
        $datos['telefono'] ?? null,
        $datos['email'] ?? null,
        $datos['direccion'] ?? null,
        $datos['ciudad'] ?? null,
        $datos['estado_civil'] ?? null,
        $datos['seccion'] ?? null,
        $datos['estado'] ?? 'Activo',
        $id
    ]);
}

function eliminarEstudiante($db, $id) {
    $sql = "DELETE FROM estudiantes WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$id]);
}

// Funciones para operaciones con representantes
function obtenerRepresentantes($db) {
    $sql = "SELECT * FROM representantes ORDER BY nombre ASC";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerRepresentantePorId($db, $id) {
    $sql = "SELECT * FROM representantes WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function agregarRepresentante($db, $datos) {
    $sql = "INSERT INTO representantes (cedula, nombre, apellido, parentesco, 
            telefono, email, direccion, ciudad, ocupacion, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $datos['cedula'],
        $datos['nombre'],
        $datos['apellido'],
        $datos['parentesco'] ?? null,
        $datos['telefono'] ?? null,
        $datos['email'] ?? null,
        $datos['direccion'] ?? null,
        $datos['ciudad'] ?? null,
        $datos['ocupacion'] ?? null,
        $datos['estado'] ?? 'Activo'
    ]);
}

function actualizarRepresentante($db, $id, $datos) {
    $sql = "UPDATE representantes SET 
            cedula = ?, nombre = ?, apellido = ?, parentesco = ?, 
            telefono = ?, email = ?, direccion = ?, ciudad = ?, ocupacion = ?, estado = ?
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $datos['cedula'],
        $datos['nombre'],
        $datos['apellido'],
        $datos['parentesco'] ?? null,
        $datos['telefono'] ?? null,
        $datos['email'] ?? null,
        $datos['direccion'] ?? null,
        $datos['ciudad'] ?? null,
        $datos['ocupacion'] ?? null,
        $datos['estado'] ?? 'Activo',
        $id
    ]);
}

function eliminarRepresentante($db, $id) {
    $sql = "DELETE FROM representantes WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$id]);
}
?>

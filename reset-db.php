<?php
/**
 * Script de Reset de Base de Datos
 * Elimina y recrea todas las tablas
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'escuela_tecnica');

// Conexión sin seleccionar BD
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "Conectado a MySQL...<br>";

// Crear base de datos
$sql_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conexion->query($sql_db)) {
    echo "Base de datos creada/verificada...<br>";
} else {
    die("Error al crear BD: " . $conexion->error);
}

// Seleccionar BD
$conexion->select_db(DB_NAME);
echo "Base de datos seleccionada...<br>";

// Establecer charset
$conexion->set_charset("utf8mb4");

// Eliminar todas las tablas existentes (en orden inverso de dependencias)
$tablas_a_eliminar = [
    'tareas',
    'material_clase',
    'examenes',
    'calificaciones',
    'representante_estudiante',
    'estudiante_representante',
    'asistencias',
    'documentos_pasantia',
    'informacion_pasantia',
    'noticias',
    'estudiantes',
    'representantes'
];

foreach ($tablas_a_eliminar as $tabla) {
    $conexion->query("DROP TABLE IF EXISTS $tabla");
    echo "Tabla $tabla eliminada...<br>";
}

echo "Todas las tablas eliminadas. Recreando...<br><br>";

// Recrear tablas
$sql_tablas = [
    "CREATE TABLE IF NOT EXISTS estudiantes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cedula VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        genero VARCHAR(20),
        fecha_nacimiento DATE,
        nivel_academico VARCHAR(50),
        seccion VARCHAR(20),
        estado_civil VARCHAR(30),
        email VARCHAR(100),
        telefono VARCHAR(20),
        direccion VARCHAR(255),
        ciudad VARCHAR(100),
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cedula_est (cedula),
        KEY idx_nombre_est (nombre),
        KEY idx_nivel_est (nivel_academico)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS representantes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cedula VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        relacion VARCHAR(50),
        email VARCHAR(100),
        telefono VARCHAR(20),
        direccion VARCHAR(255),
        ciudad VARCHAR(100),
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cedula_rep (cedula),
        KEY idx_nombre_rep (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS asistencias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        fecha DATE NOT NULL,
        hora TIME,
        estado VARCHAR(20) DEFAULT 'Presente',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_est_fecha (id_estudiante, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS noticias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        categoria VARCHAR(100),
        contenido LONGTEXT,
        autor VARCHAR(100),
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_publicacion DATE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_categoria (categoria),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS documentos_pasantia (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        tipo_documento VARCHAR(100),
        descripcion LONGTEXT,
        contenido LONGTEXT,
        archivo_nombre VARCHAR(255),
        archivo_ruta VARCHAR(500),
        estado VARCHAR(30) DEFAULT 'Pendiente',
        fecha_carga DATE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_estudiante (id_estudiante),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS informacion_pasantia (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        empresa VARCHAR(255),
        tutor_academico VARCHAR(255),
        tutor_empresarial VARCHAR(255),
        cargo_estudiante VARCHAR(100),
        fecha_inicio DATE,
        fecha_fin DATE,
        horas_totales INT DEFAULT 0,
        horas_completadas INT DEFAULT 0,
        objetivo_pasantia LONGTEXT,
        actividades_realizadas LONGTEXT,
        competencias_adquiridas LONGTEXT,
        evaluacion_desempeno LONGTEXT,
        recomendaciones LONGTEXT,
        calificacion_final DECIMAL(5,2),
        observaciones LONGTEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_estudiante (id_estudiante),
        UNIQUE KEY unica_pasantia (id_estudiante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS calificaciones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        materia VARCHAR(100) NOT NULL,
        lapso INT NOT NULL,
        nota_primera_evaluacion DECIMAL(5,2),
        nota_segunda_evaluacion DECIMAL(5,2),
        nota_tercera_evaluacion DECIMAL(5,2),
        nota_promedio DECIMAL(5,2),
        observaciones TEXT,
        fecha_carga DATE,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_estudiante (id_estudiante),
        KEY idx_lapso (lapso),
        KEY idx_materia (materia),
        UNIQUE KEY unica_calificacion (id_estudiante, materia, lapso)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS estudiante_representante (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_representante INT NOT NULL,
        id_estudiante INT NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_representante (id_representante),
        KEY idx_estudiante (id_estudiante),
        UNIQUE KEY unica_relacion (id_representante, id_estudiante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS tareas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        fecha_entrega DATE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_seccion (seccion),
        KEY idx_profesor (id_profesor),
        KEY idx_fecha (fecha_entrega)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS material_clase (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        materia VARCHAR(100),
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        contenido LONGTEXT,
        archivo_url VARCHAR(255),
        fecha_publicacion DATE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_seccion (seccion),
        KEY idx_profesor (id_profesor),
        KEY idx_fecha (fecha_publicacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS examenes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        materia VARCHAR(100),
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        fecha_examen DATE NOT NULL,
        hora_examen TIME,
        lugar VARCHAR(100),
        observaciones LONGTEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_seccion (seccion),
        KEY idx_profesor (id_profesor),
        KEY idx_fecha (fecha_examen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$contador = 0;
foreach ($sql_tablas as $sql) {
    if (!empty($sql)) {
        if ($conexion->query($sql)) {
            $contador++;
            echo "✓ Tabla creada exitosamente<br>";
        } else {
            echo "✗ Error: " . $conexion->error . "<br>";
        }
    }
}

echo "<br><strong>Proceso completado:</strong><br>";
echo "- $contador tablas recreadas exitosamente<br>";
echo "- Base de datos lista para usar<br>";

$conexion->close();
?>

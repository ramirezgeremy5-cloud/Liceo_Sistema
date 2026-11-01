<?php
/**
 * Configuración de la Base de Datos MySQL
 * IMPORTANTE: Actualiza estos datos con tus credenciales MySQL
 */

define('DB_HOST', 'localhost');      // Host de MySQL
define('DB_USER', 'root');           // Usuario MySQL (por defecto en Laragon es 'root')
define('DB_PASS', '');               // Contraseña MySQL (por defecto en Laragon está vacía)
define('DB_NAME', 'escuela_tecnica'); // Nombre de la base de datos

// Crear conexión
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Verificar conexión
if ($conexion->connect_error) {
    // Mostrar error amigable pero permitir continuar
    echo "<div style='background: #fee2e2; color: #991b1b; padding: 20px; margin: 20px; border-radius: 8px; border: 1px solid #fca5a5;'>";
    echo "<strong>Advertencia de Base de Datos:</strong><br>";
    echo "No se pudo conectar a MySQL. Por favor:<br>";
    echo "1. Inicia Laragon y MySQL<br>";
    echo "2. Crea la base de datos 'escuela_tecnica' en phpMyAdmin<br>";
    echo "3. Ejecuta el SQL del archivo INSTALAR_BASE_DATOS.txt<br>";
    echo "<br><small>Error: " . htmlspecialchars($conexion->connect_error) . "</small>";
    echo "</div>";
    $conexion = null;
}

// Crear base de datos si no existe
$sql_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conexion->query($sql_db) === TRUE) {
    // Seleccionar la base de datos
    $conexion->select_db(DB_NAME);
} else {
    die("Error al crear/seleccionar la base de datos: " . $conexion->error);
}

// Establecer charset a UTF-8
$conexion->set_charset("utf8mb4");

// Crear tablas si no existen
$sql_tablas = [
    // Tabla de Estudiantes
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
    
    // Tabla de Representantes
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
    
    // Tabla de relación Estudiante-Representante
    "CREATE TABLE IF NOT EXISTS estudiante_representante (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        id_representante INT NOT NULL,
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
        FOREIGN KEY (id_representante) REFERENCES representantes(id) ON DELETE CASCADE,
        UNIQUE KEY unica_relacion (id_estudiante, id_representante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Asistencias
    "CREATE TABLE IF NOT EXISTS asistencias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        estado VARCHAR(20) DEFAULT 'Presente',
        observaciones VARCHAR(255),
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
        KEY idx_estudiante (id_estudiante),
        KEY idx_fecha (fecha),
        UNIQUE KEY unica_asistencia (id_estudiante, fecha)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Noticias
    "CREATE TABLE IF NOT EXISTS noticias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT NOT NULL,
        categoria VARCHAR(50) NOT NULL,
        fecha_publicacion DATE NOT NULL,
        autor VARCHAR(100),
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_categoria (categoria),
        KEY idx_fecha (fecha_publicacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Documentos de Pasantías
    "CREATE TABLE IF NOT EXISTS documentos_pasantia (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        tipo_documento VARCHAR(100) NOT NULL,
        descripcion TEXT,
        contenido LONGTEXT,
        archivo_nombre VARCHAR(255),
        archivo_ruta VARCHAR(255),
        fecha_carga DATE NOT NULL,
        estado VARCHAR(20) DEFAULT 'Pendiente',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
        KEY idx_estudiante (id_estudiante),
        KEY idx_tipo (tipo_documento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Información de Pasantías
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
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
        KEY idx_estudiante (id_estudiante),
        UNIQUE KEY unica_pasantia (id_estudiante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Notas y Calificaciones
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
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
        KEY idx_estudiante (id_estudiante),
        KEY idx_lapso (lapso),
        KEY idx_materia (materia),
        UNIQUE KEY unica_calificacion (id_estudiante, materia, lapso)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Tareas
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
    
    // Tabla de Material de Clase
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
    
    // Tabla de Exámenes
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Secciones del Profesor
    "CREATE TABLE IF NOT EXISTS secciones_profesor (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        nombre_seccion VARCHAR(100) NOT NULL,
        grado VARCHAR(50),
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado VARCHAR(20) DEFAULT 'Activa',
        KEY idx_profesor (id_profesor),
        KEY idx_nombre (nombre_seccion),
        UNIQUE KEY unica_seccion (id_profesor, nombre_seccion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Actividades/Lecciones
    "CREATE TABLE IF NOT EXISTS actividades_clase (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_seccion INT NOT NULL,
        id_profesor INT NOT NULL,
        nombre_actividad VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        fecha_evaluacion DATE,
        temas_vistos LONGTEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_seccion (id_seccion),
        KEY idx_profesor (id_profesor),
        KEY idx_fecha (fecha_evaluacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Disponibilidad del Profesor
    "CREATE TABLE IF NOT EXISTS disponibilidad_profesor (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        dia_semana VARCHAR(20) NOT NULL,
        hora_inicio TIME NOT NULL,
        hora_fin TIME NOT NULL,
        tipo_atencion VARCHAR(50) DEFAULT 'Presencial',
        capacidad INT DEFAULT 1,
        reservas_actuales INT DEFAULT 0,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado VARCHAR(20) DEFAULT 'Activa',
        KEY idx_profesor (id_profesor),
        KEY idx_dia (dia_semana)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Reuniones/Entrevistas
    "CREATE TABLE IF NOT EXISTS reuniones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_profesor INT NOT NULL,
        id_representante INT NOT NULL,
        id_estudiante INT,
        fecha_reunion DATE NOT NULL,
        hora_reunion TIME NOT NULL,
        tipo_reunion VARCHAR(50) DEFAULT 'Presencial',
        asunto VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        estado VARCHAR(30) DEFAULT 'Pendiente',
        nota_profesor LONGTEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_profesor (id_profesor),
        KEY idx_representante (id_representante),
        KEY idx_fecha (fecha_reunion),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Mensajes Internos
    "CREATE TABLE IF NOT EXISTS mensajes_internos (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_remitente INT NOT NULL,
        id_destinatario INT NOT NULL,
        tipo_remitente VARCHAR(30) NOT NULL,
        tipo_destinatario VARCHAR(30) NOT NULL,
        asunto VARCHAR(255),
        contenido LONGTEXT NOT NULL,
        leido BOOLEAN DEFAULT FALSE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_remitente (id_remitente),
        KEY idx_destinatario (id_destinatario),
        KEY idx_fecha (fecha_creacion),
        KEY idx_leido (leido)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Reuniones Generales (para Profesores/Admins)
    "CREATE TABLE IF NOT EXISTS reuniones_generales (
        id INT PRIMARY KEY AUTO_INCREMENT,
        seccion VARCHAR(50) NOT NULL,
        asunto VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        fecha_reunion DATE NOT NULL,
        hora_reunion TIME,
        creado_por INT NOT NULL,
        rol_creador VARCHAR(30),
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_seccion (seccion),
        KEY idx_fecha (fecha_reunion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Asistencia a Reuniones
    "CREATE TABLE IF NOT EXISTS asistencia_reuniones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_reunion INT NOT NULL,
        id_usuario INT NOT NULL,
        rol_usuario VARCHAR(30),
        confirmado BOOLEAN DEFAULT FALSE,
        fecha_confirmacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_reunion (id_reunion),
        KEY idx_usuario (id_usuario),
        UNIQUE KEY unica_asistencia (id_reunion, id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Reportes de Conducta (Bitácora Escolar)
    "CREATE TABLE IF NOT EXISTS reportes_conducta (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_estudiante INT NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        id_profesor INT NOT NULL,
        tipo_reporte VARCHAR(50) NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descripcion LONGTEXT,
        fecha_reporte DATE NOT NULL,
        hora_reporte TIME,
        evidencia VARCHAR(255),
        estado VARCHAR(30) DEFAULT 'Pendiente',
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_estudiante (id_estudiante),
        KEY idx_seccion (seccion),
        KEY idx_profesor (id_profesor),
        KEY idx_fecha (fecha_reporte),
        KEY idx_tipo (tipo_reporte)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tabla de Firmas Digitales de Amonestaciones
    "CREATE TABLE IF NOT EXISTS firmas_amonestaciones (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_reporte INT NOT NULL,
        id_representante INT NOT NULL,
        id_estudiante INT NOT NULL,
        confirmado BOOLEAN DEFAULT FALSE,
        fecha_lectura TIMESTAMP,
        ip_direccion VARCHAR(45),
        navegador VARCHAR(255),
        observaciones LONGTEXT,
        fecha_firma TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_reporte (id_reporte),
        KEY idx_representante (id_representante),
        KEY idx_estudiante (id_estudiante),
        UNIQUE KEY unica_firma (id_reporte, id_representante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Ejecutar queries
foreach ($sql_tablas as $sql) {
    if (!empty($sql)) {
        @$conexion->query($sql);
    }
}

?>

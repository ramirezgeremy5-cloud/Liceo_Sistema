<?php
/**
 * Script para inicializar/resetear la base de datos
 * Accede a: http://localhost/liceo/init-db.php
 */

require_once 'includes/config.php';

// Obtener las queries de creación de tablas
$tablas_queries = [
    // Estudiantes
    "CREATE TABLE IF NOT EXISTS estudiantes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cedula VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        telefono VARCHAR(20),
        seccion VARCHAR(50),
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Representantes
    "CREATE TABLE IF NOT EXISTS representantes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        cedula VARCHAR(20) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        telefono VARCHAR(20),
        direccion LONGTEXT,
        estado VARCHAR(20) DEFAULT 'Activo',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Estudiante-Representante
    "CREATE TABLE IF NOT EXISTS estudiante_representante (
        id INT PRIMARY KEY AUTO_INCREMENT,
        id_representante INT NOT NULL,
        id_estudiante INT NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_representante (id_representante),
        KEY idx_estudiante (id_estudiante),
        UNIQUE KEY unica_relacion (id_representante, id_estudiante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tareas
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
    
    // Material de Clase
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
    
    // Exámenes
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
    
    // Secciones del Profesor
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
    
    // Actividades/Lecciones
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$exito = true;
$mensajes = [];

foreach ($tablas_queries as $indice => $query) {
    if ($conexion->query($query)) {
        $mensajes[] = "✓ Tabla " . ($indice + 1) . " creada exitosamente";
    } else {
        $exito = false;
        $mensajes[] = "✗ Error al crear tabla " . ($indice + 1) . ": " . $conexion->error;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicialización de Base de Datos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #6b4423;
            padding-bottom: 10px;
        }
        .mensaje {
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .exito {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        .status {
            text-align: center;
            padding: 20px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .status.ok {
            background: #d1fae5;
            color: #065f46;
        }
        .status.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .link {
            text-align: center;
            margin-top: 20px;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #6b4423;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        a:hover {
            background: #8b5a2b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inicialización de Base de Datos</h1>
        
        <h2>Resultado de la creación de tablas:</h2>
        
        <?php foreach ($mensajes as $msg): ?>
            <div class="mensaje <?php echo (strpos($msg, '✓') === 0) ? 'exito' : 'error'; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
        
        <div class="status <?php echo $exito ? 'ok' : 'error'; ?>">
            <h2><?php echo $exito ? 'BASE DE DATOS INICIALIZADA CORRECTAMENTE' : 'HUBO ERRORES DURANTE LA INICIALIZACIÓN'; ?></h2>
        </div>
        
        <div class="link">
            <a href="login.php">Ir al Login</a>
            <a href="index.php">Ir al Inicio</a>
        </div>
    </div>
</body>
</html>

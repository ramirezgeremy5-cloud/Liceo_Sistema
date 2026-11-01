<?php
require_once 'config.php';

// ESTUDIANTES
function agregarEstudiante($conexion, $datos) {
    if (!$conexion) return false;
    
    $cedula = $conexion->real_escape_string($datos['cedula']);
    $nombre = $conexion->real_escape_string($datos['nombre']);
    $apellido = $conexion->real_escape_string($datos['apellido']);
    $genero = $conexion->real_escape_string($datos['genero'] ?? '');
    $fecha_nacimiento = $conexion->real_escape_string($datos['fecha_nacimiento'] ?? '');
    $nivel_academico = $conexion->real_escape_string($datos['nivel_academico'] ?? '');
    $seccion = $conexion->real_escape_string($datos['seccion'] ?? '');
    $estado_civil = $conexion->real_escape_string($datos['estado_civil'] ?? '');
    $email = $conexion->real_escape_string($datos['email'] ?? '');
    $telefono = $conexion->real_escape_string($datos['telefono'] ?? '');
    $direccion = $conexion->real_escape_string($datos['direccion'] ?? '');
    $ciudad = $conexion->real_escape_string($datos['ciudad'] ?? '');
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Activo');

    $sql = "INSERT INTO estudiantes (cedula, nombre, apellido, genero, fecha_nacimiento, nivel_academico, seccion, estado_civil, email, telefono, direccion, ciudad, estado) 
            VALUES ('$cedula', '$nombre', '$apellido', '$genero', '$fecha_nacimiento', '$nivel_academico', '$seccion', '$estado_civil', '$email', '$telefono', '$direccion', '$ciudad', '$estado')";
    
    return $conexion->query($sql);
}

function obtenerEstudiantes($conexion) {
    if (!$conexion) return [];
    
    $sql = "SELECT * FROM estudiantes ORDER BY apellido, nombre";
    $resultado = $conexion->query($sql);
    $estudiantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $estudiantes[] = $fila;
        }
    }
    
    return $estudiantes;
}

function obtenerEstudiantePorId($conexion, $id) {
    $id = (int)$id;
    $sql = "SELECT * FROM estudiantes WHERE id = $id";
    $resultado = $conexion->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
}

function actualizarEstudiante($conexion, $id, $datos) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $cedula = $conexion->real_escape_string($datos['cedula']);
    $nombre = $conexion->real_escape_string($datos['nombre']);
    $apellido = $conexion->real_escape_string($datos['apellido']);
    $genero = $conexion->real_escape_string($datos['genero'] ?? '');
    $fecha_nacimiento = $conexion->real_escape_string($datos['fecha_nacimiento'] ?? '');
    $nivel_academico = $conexion->real_escape_string($datos['nivel_academico'] ?? '');
    $seccion = $conexion->real_escape_string($datos['seccion'] ?? '');
    $estado_civil = $conexion->real_escape_string($datos['estado_civil'] ?? '');
    $email = $conexion->real_escape_string($datos['email'] ?? '');
    $telefono = $conexion->real_escape_string($datos['telefono'] ?? '');
    $direccion = $conexion->real_escape_string($datos['direccion'] ?? '');
    $ciudad = $conexion->real_escape_string($datos['ciudad'] ?? '');
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Activo');

    $sql = "UPDATE estudiantes SET cedula='$cedula', nombre='$nombre', apellido='$apellido', genero='$genero', 
            fecha_nacimiento='$fecha_nacimiento', nivel_academico='$nivel_academico', seccion='$seccion', 
            estado_civil='$estado_civil', email='$email', telefono='$telefono', direccion='$direccion', 
            ciudad='$ciudad', estado='$estado' WHERE id = $id";
    
    return $conexion->query($sql);
}

function eliminarEstudiante($conexion, $id) {
    $id = (int)$id;
    $sql = "DELETE FROM estudiantes WHERE id = $id";
    return $conexion->query($sql);
}

function buscarEstudiantes($conexion, $termino) {
    if (!$conexion) return [];
    
    $termino = $conexion->real_escape_string('%' . $termino . '%');
    $sql = "SELECT * FROM estudiantes WHERE cedula LIKE '$termino' OR nombre LIKE '$termino' OR apellido LIKE '$termino' OR email LIKE '$termino' ORDER BY apellido, nombre";
    $resultado = $conexion->query($sql);
    $estudiantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $estudiantes[] = $fila;
        }
    }
    
    return $estudiantes;
}

// REPRESENTANTES
function agregarRepresentante($conexion, $datos) {
    $cedula = $conexion->real_escape_string($datos['cedula']);
    $nombre = $conexion->real_escape_string($datos['nombre']);
    $apellido = $conexion->real_escape_string($datos['apellido']);
    $relacion = $conexion->real_escape_string($datos['relacion'] ?? '');
    $email = $conexion->real_escape_string($datos['email'] ?? '');
    $telefono = $conexion->real_escape_string($datos['telefono'] ?? '');
    $direccion = $conexion->real_escape_string($datos['direccion'] ?? '');
    $ciudad = $conexion->real_escape_string($datos['ciudad'] ?? '');

    $sql = "INSERT INTO representantes (cedula, nombre, apellido, relacion, email, telefono, direccion, ciudad) 
            VALUES ('$cedula', '$nombre', '$apellido', '$relacion', '$email', '$telefono', '$direccion', '$ciudad')";
    
    return $conexion->query($sql);
}

function obtenerRepresentantes($conexion) {
    $sql = "SELECT * FROM representantes ORDER BY apellido, nombre";
    $resultado = $conexion->query($sql);
    $representantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $representantes[] = $fila;
        }
    }
    
    return $representantes;
}

function buscarRepresentantes($conexion, $termino) {
    if (!$conexion) return [];
    
    $termino = $conexion->real_escape_string('%' . $termino . '%');
    $sql = "SELECT * FROM representantes WHERE cedula LIKE '$termino' OR nombre LIKE '$termino' OR apellido LIKE '$termino' OR email LIKE '$termino' ORDER BY apellido, nombre";
    $resultado = $conexion->query($sql);
    $representantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $representantes[] = $fila;
        }
    }
    
    return $representantes;
}

// ASISTENCIAS
function registrarAsistencia($conexion, $id_estudiante) {
    if (!$conexion) return false;
    
    $id_estudiante = (int)$id_estudiante;
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    
    // Verificar si ya existe registro de hoy
    $sql_check = "SELECT id FROM asistencias WHERE id_estudiante = $id_estudiante AND fecha = '$fecha'";
    $resultado = $conexion->query($sql_check);
    
    if ($resultado && $resultado->num_rows > 0) {
        return false; // Ya asistió hoy
    }
    
    $sql = "INSERT INTO asistencias (id_estudiante, fecha, hora, estado) 
            VALUES ($id_estudiante, '$fecha', '$hora', 'Presente')";
    
    return $conexion->query($sql);
}

function obtenerAsistenciasDelDia($conexion, $fecha = null) {
    if (!$conexion) return [];
    
    if (!$fecha) {
        $fecha = date('Y-m-d');
    }
    
    $fecha = $conexion->real_escape_string($fecha);
    
    $sql = "SELECT a.id, a.id_estudiante, a.fecha, a.hora, a.estado, 
            e.cedula, e.nombre, e.apellido, e.nivel_academico, e.seccion
            FROM asistencias a 
            JOIN estudiantes e ON a.id_estudiante = e.id 
            WHERE a.fecha = '$fecha'
            ORDER BY a.hora DESC";
    
    $resultado = $conexion->query($sql);
    $asistencias = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $asistencias[] = $fila;
        }
    }
    
    return $asistencias;
}

function obtenerAsistenciasEstudiante($conexion, $id_estudiante, $fecha_inicio = null, $fecha_fin = null) {
    if (!$conexion) return [];
    
    $id_estudiante = (int)$id_estudiante;
    
    if (!$fecha_inicio) {
        $fecha_inicio = date('Y-m-01'); // Primer día del mes
    }
    if (!$fecha_fin) {
        $fecha_fin = date('Y-m-d'); // Hoy
    }
    
    $fecha_inicio = $conexion->real_escape_string($fecha_inicio);
    $fecha_fin = $conexion->real_escape_string($fecha_fin);
    
    $sql = "SELECT * FROM asistencias 
            WHERE id_estudiante = $id_estudiante 
            AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
            ORDER BY fecha DESC";
    
    $resultado = $conexion->query($sql);
    $asistencias = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $asistencias[] = $fila;
        }
    }
    
    return $asistencias;
}

function obtenerEstadisticasAsistencia($conexion, $mes = null, $año = null) {
    if (!$conexion) return [];
    
    if (!$mes) $mes = date('m');
    if (!$año) $año = date('Y');
    
    $mes = (int)$mes;
    $año = (int)$año;
    
    $sql = "SELECT e.id, e.cedula, e.nombre, e.apellido, 
            COUNT(CASE WHEN a.estado = 'Presente' THEN 1 END) as presentes,
            COUNT(CASE WHEN a.estado = 'Ausente' THEN 1 END) as ausentes,
            COUNT(a.id) as total_dias
            FROM estudiantes e
            LEFT JOIN asistencias a ON e.id = a.id_estudiante 
            AND YEAR(a.fecha) = $año AND MONTH(a.fecha) = $mes
            GROUP BY e.id, e.cedula, e.nombre, e.apellido
            ORDER BY e.apellido, e.nombre";
    
    $resultado = $conexion->query($sql);
    $estadisticas = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $estadisticas[] = $fila;
        }
    }
    
    return $estadisticas;
}

// NOTICIAS
function agregarNoticia($conexion, $datos) {
    if (!$conexion) return false;
    
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion']);
    $categoria = $conexion->real_escape_string($datos['categoria']);
    $fecha_publicacion = $conexion->real_escape_string($datos['fecha_publicacion']);
    $autor = $conexion->real_escape_string($datos['autor'] ?? '');
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Activo');
    
    $sql = "INSERT INTO noticias (titulo, descripcion, categoria, fecha_publicacion, autor, estado) 
            VALUES ('$titulo', '$descripcion', '$categoria', '$fecha_publicacion', '$autor', '$estado')";
    
    return $conexion->query($sql);
}

function obtenerNoticias($conexion) {
    if (!$conexion) return [];
    
    $sql = "SELECT * FROM noticias WHERE estado = 'Activo' ORDER BY fecha_publicacion DESC";
    $resultado = $conexion->query($sql);
    $noticias = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $noticias[] = $fila;
        }
    }
    
    return $noticias;
}

function obtenerNoticiasPorCategoria($conexion, $categoria) {
    if (!$conexion) return [];
    
    $categoria = $conexion->real_escape_string($categoria);
    $sql = "SELECT * FROM noticias WHERE categoria = '$categoria' AND estado = 'Activo' ORDER BY fecha_publicacion DESC";
    $resultado = $conexion->query($sql);
    $noticias = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $noticias[] = $fila;
        }
    }
    
    return $noticias;
}

function obtenerNoticiaPorId($conexion, $id) {
    if (!$conexion) return null;
    
    $id = (int)$id;
    $sql = "SELECT * FROM noticias WHERE id = $id";
    $resultado = $conexion->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
}

function actualizarNoticia($conexion, $id, $datos) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion']);
    $categoria = $conexion->real_escape_string($datos['categoria']);
    $fecha_publicacion = $conexion->real_escape_string($datos['fecha_publicacion']);
    $autor = $conexion->real_escape_string($datos['autor'] ?? '');
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Activo');
    
    $sql = "UPDATE noticias SET titulo='$titulo', descripcion='$descripcion', categoria='$categoria', 
            fecha_publicacion='$fecha_publicacion', autor='$autor', estado='$estado' WHERE id = $id";
    
    return $conexion->query($sql);
}

function eliminarNoticia($conexion, $id) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $sql = "DELETE FROM noticias WHERE id = $id";
    return $conexion->query($sql);
}

// DOCUMENTOS DE PASANTÍA
function agregarDocumentoPasantia($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_estudiante = (int)$datos['id_estudiante'];
    $tipo_documento = $conexion->real_escape_string($datos['tipo_documento']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $contenido = $conexion->real_escape_string($datos['contenido'] ?? '');
    $archivo_nombre = $conexion->real_escape_string($datos['archivo_nombre'] ?? '');
    $archivo_ruta = $conexion->real_escape_string($datos['archivo_ruta'] ?? '');
    $fecha_carga = $conexion->real_escape_string($datos['fecha_carga'] ?? date('Y-m-d'));
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Pendiente');
    
    $sql = "INSERT INTO documentos_pasantia (id_estudiante, tipo_documento, descripcion, contenido, 
            archivo_nombre, archivo_ruta, fecha_carga, estado) 
            VALUES ($id_estudiante, '$tipo_documento', '$descripcion', '$contenido', 
            '$archivo_nombre', '$archivo_ruta', '$fecha_carga', '$estado')";
    
    return $conexion->query($sql);
}

function obtenerDocumentosPasantia($conexion, $id_estudiante = null) {
    if (!$conexion) return [];
    
    $sql = "SELECT * FROM documentos_pasantia";
    
    if ($id_estudiante) {
        $id_estudiante = (int)$id_estudiante;
        $sql .= " WHERE id_estudiante = $id_estudiante";
    }
    
    $sql .= " ORDER BY fecha_carga DESC";
    
    $resultado = $conexion->query($sql);
    $documentos = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $documentos[] = $fila;
        }
    }
    
    return $documentos;
}

function obtenerDocumentoPasantiaPorId($conexion, $id) {
    if (!$conexion) return null;
    
    $id = (int)$id;
    $sql = "SELECT * FROM documentos_pasantia WHERE id = $id";
    $resultado = $conexion->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
}

function actualizarDocumentoPasantia($conexion, $id, $datos) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $tipo_documento = $conexion->real_escape_string($datos['tipo_documento']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $contenido = $conexion->real_escape_string($datos['contenido'] ?? '');
    $archivo_nombre = $conexion->real_escape_string($datos['archivo_nombre'] ?? '');
    $archivo_ruta = $conexion->real_escape_string($datos['archivo_ruta'] ?? '');
    $estado = $conexion->real_escape_string($datos['estado'] ?? 'Pendiente');
    
    $sql = "UPDATE documentos_pasantia SET tipo_documento='$tipo_documento', descripcion='$descripcion', 
            contenido='$contenido', archivo_nombre='$archivo_nombre', archivo_ruta='$archivo_ruta', 
            estado='$estado' WHERE id = $id";
    
    return $conexion->query($sql);
}

function eliminarDocumentoPasantia($conexion, $id) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $sql = "DELETE FROM documentos_pasantia WHERE id = $id";
    return $conexion->query($sql);
}

// INFORMACIÓN DE PASANTÍA
function agregarInformacionPasantia($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_estudiante = (int)$datos['id_estudiante'];
    $empresa = $conexion->real_escape_string($datos['empresa'] ?? '');
    $tutor_academico = $conexion->real_escape_string($datos['tutor_academico'] ?? '');
    $tutor_empresarial = $conexion->real_escape_string($datos['tutor_empresarial'] ?? '');
    $cargo_estudiante = $conexion->real_escape_string($datos['cargo_estudiante'] ?? '');
    $fecha_inicio = $conexion->real_escape_string($datos['fecha_inicio'] ?? '');
    $fecha_fin = $conexion->real_escape_string($datos['fecha_fin'] ?? '');
    $horas_totales = (int)($datos['horas_totales'] ?? 0);
    $horas_completadas = (int)($datos['horas_completadas'] ?? 0);
    $objetivo_pasantia = $conexion->real_escape_string($datos['objetivo_pasantia'] ?? '');
    $actividades_realizadas = $conexion->real_escape_string($datos['actividades_realizadas'] ?? '');
    $competencias_adquiridas = $conexion->real_escape_string($datos['competencias_adquiridas'] ?? '');
    $evaluacion_desempeno = $conexion->real_escape_string($datos['evaluacion_desempeno'] ?? '');
    $recomendaciones = $conexion->real_escape_string($datos['recomendaciones'] ?? '');
    $calificacion_final = (float)($datos['calificacion_final'] ?? 0);
    $observaciones = $conexion->real_escape_string($datos['observaciones'] ?? '');
    
    $sql = "INSERT INTO informacion_pasantia (id_estudiante, empresa, tutor_academico, tutor_empresarial, 
            cargo_estudiante, fecha_inicio, fecha_fin, horas_totales, horas_completadas, objetivo_pasantia, 
            actividades_realizadas, competencias_adquiridas, evaluacion_desempeno, recomendaciones, 
            calificacion_final, observaciones) 
            VALUES ($id_estudiante, '$empresa', '$tutor_academico', '$tutor_empresarial', '$cargo_estudiante', 
            '$fecha_inicio', '$fecha_fin', $horas_totales, $horas_completadas, '$objetivo_pasantia', 
            '$actividades_realizadas', '$competencias_adquiridas', '$evaluacion_desempeno', '$recomendaciones', 
            $calificacion_final, '$observaciones')
            ON DUPLICATE KEY UPDATE empresa='$empresa', tutor_academico='$tutor_academico', 
            tutor_empresarial='$tutor_empresarial', cargo_estudiante='$cargo_estudiante', 
            fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin', horas_totales=$horas_totales, 
            horas_completadas=$horas_completadas, objetivo_pasantia='$objetivo_pasantia', 
            actividades_realizadas='$actividades_realizadas', competencias_adquiridas='$competencias_adquiridas', 
            evaluacion_desempeno='$evaluacion_desempeno', recomendaciones='$recomendaciones', 
            calificacion_final=$calificacion_final, observaciones='$observaciones'";
    
    return $conexion->query($sql);
}

function obtenerInformacionPasantia($conexion, $id_estudiante) {
    if (!$conexion) return null;
    
    $id_estudiante = (int)$id_estudiante;
    $sql = "SELECT * FROM informacion_pasantia WHERE id_estudiante = $id_estudiante";
    $resultado = $conexion->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
}

function obtenerTodasInformacionesPasantia($conexion) {
    if (!$conexion) return [];
    
    $sql = "SELECT ip.*, e.cedula, e.nombre, e.apellido FROM informacion_pasantia ip 
            JOIN estudiantes e ON ip.id_estudiante = e.id 
            ORDER BY e.apellido, e.nombre";
    
    $resultado = $conexion->query($sql);
    $informaciones = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $informaciones[] = $fila;
        }
    }
    
    return $informaciones;
}

// CALIFICACIONES
function agregarCalificacion($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_estudiante = (int)$datos['id_estudiante'];
    $materia = $conexion->real_escape_string($datos['materia']);
    $lapso = (int)$datos['lapso'];
    $nota_primera = (float)($datos['nota_primera_evaluacion'] ?? 0);
    $nota_segunda = (float)($datos['nota_segunda_evaluacion'] ?? 0);
    $nota_tercera = (float)($datos['nota_tercera_evaluacion'] ?? 0);
    $nota_promedio = ($nota_primera + $nota_segunda + $nota_tercera) / 3;
    $observaciones = $conexion->real_escape_string($datos['observaciones'] ?? '');
    $fecha_carga = $conexion->real_escape_string($datos['fecha_carga'] ?? date('Y-m-d'));
    
    $sql = "INSERT INTO calificaciones (id_estudiante, materia, lapso, nota_primera_evaluacion, 
            nota_segunda_evaluacion, nota_tercera_evaluacion, nota_promedio, observaciones, fecha_carga) 
            VALUES ($id_estudiante, '$materia', $lapso, $nota_primera, $nota_segunda, $nota_tercera, 
            $nota_promedio, '$observaciones', '$fecha_carga')
            ON DUPLICATE KEY UPDATE nota_primera_evaluacion=$nota_primera, nota_segunda_evaluacion=$nota_segunda,
            nota_tercera_evaluacion=$nota_tercera, nota_promedio=$nota_promedio, observaciones='$observaciones'";
    
    return $conexion->query($sql);
}

function obtenerCalificacionesEstudiante($conexion, $id_estudiante, $lapso = null) {
    if (!$conexion) return [];
    
    $id_estudiante = (int)$id_estudiante;
    $sql = "SELECT * FROM calificaciones WHERE id_estudiante = $id_estudiante";
    
    if ($lapso) {
        $lapso = (int)$lapso;
        $sql .= " AND lapso = $lapso";
    }
    
    $sql .= " ORDER BY materia ASC";
    
    $resultado = $conexion->query($sql);
    $calificaciones = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $calificaciones[] = $fila;
        }
    }
    
    return $calificaciones;
}

function obtenerAsistenciaEstudiante($conexion, $id_estudiante, $fecha_inicio = null, $fecha_fin = null) {
    if (!$conexion) return [];
    
    $id_estudiante = (int)$id_estudiante;
    $sql = "SELECT * FROM asistencias WHERE id_estudiante = $id_estudiante";
    
    if ($fecha_inicio && $fecha_fin) {
        $fecha_inicio = $conexion->real_escape_string($fecha_inicio);
        $fecha_fin = $conexion->real_escape_string($fecha_fin);
        $sql .= " AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    
    $sql .= " ORDER BY fecha DESC";
    
    $resultado = $conexion->query($sql);
    $asistencias = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $asistencias[] = $fila;
        }
    }
    
    return $asistencias;
}

function obtenerEstadisticasAsistenciaEstudiante($conexion, $id_estudiante) {
    if (!$conexion) return null;
    
    $id_estudiante = (int)$id_estudiante;
    $sql = "SELECT 
                COUNT(*) as total_dias,
                SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as presentes,
                SUM(CASE WHEN estado = 'Ausente' THEN 1 ELSE 0 END) as ausentes,
                SUM(CASE WHEN estado = 'Tarde' THEN 1 ELSE 0 END) as tardes,
                ROUND((SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje_asistencia
            FROM asistencias 
            WHERE id_estudiante = $id_estudiante 
            AND YEAR(fecha) = YEAR(CURDATE())";
    
    $resultado = $conexion->query($sql);
    return $resultado ? $resultado->fetch_assoc() : null;
}

// RELACIÓN REPRESENTANTE-ESTUDIANTE
function obtenerEstudiantesDelRepresentante($conexion, $id_representante) {
    if (!$conexion) return [];
    
    $id_representante = (int)$id_representante;
    $sql = "SELECT DISTINCT e.* 
            FROM estudiantes e 
            JOIN estudiante_representante er ON e.id = er.id_estudiante
            WHERE er.id_representante = $id_representante
            ORDER BY e.apellido, e.nombre";
    
    $resultado = $conexion->query($sql);
    $estudiantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $estudiantes[] = $fila;
        }
    }
    
    return $estudiantes;
}

function asignarEstudianteARepresentante($conexion, $id_representante, $id_estudiante) {
    if (!$conexion) return false;
    
    $id_representante = (int)$id_representante;
    $id_estudiante = (int)$id_estudiante;
    
    $sql = "INSERT INTO estudiante_representante (id_representante, id_estudiante) 
            VALUES ($id_representante, $id_estudiante)
            ON DUPLICATE KEY UPDATE id_representante = $id_representante";
    
    return $conexion->query($sql);
}

// TAREAS
function agregarTarea($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_profesor = (int)$datos['id_profesor'];
    $seccion = $conexion->real_escape_string($datos['seccion']);
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $fecha_entrega = $conexion->real_escape_string($datos['fecha_entrega'] ?? date('Y-m-d'));
    
    $sql = "INSERT INTO tareas (id_profesor, seccion, titulo, descripcion, fecha_entrega) 
            VALUES ($id_profesor, '$seccion', '$titulo', '$descripcion', '$fecha_entrega')";
    
    return $conexion->query($sql);
}

function obtenerTareasPorSeccion($conexion, $seccion) {
    if (!$conexion) return [];
    
    $seccion = $conexion->real_escape_string($seccion);
    $sql = "SELECT t.*, p.nombre as profesor_nombre FROM tareas t 
            JOIN profesores p ON t.id_profesor = p.id
            WHERE t.seccion = '$seccion'
            ORDER BY t.fecha_entrega DESC";
    
    $resultado = $conexion->query($sql);
    $tareas = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $tareas[] = $fila;
        }
    }
    
    return $tareas;
}

function eliminarTarea($conexion, $id) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $sql = "DELETE FROM tareas WHERE id = $id";
    return $conexion->query($sql);
}

// MATERIAL DE CLASE
function agregarMaterialClase($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_profesor = (int)$datos['id_profesor'];
    $seccion = $conexion->real_escape_string($datos['seccion']);
    $materia = $conexion->real_escape_string($datos['materia'] ?? '');
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $contenido = $conexion->real_escape_string($datos['contenido'] ?? '');
    $archivo_url = $conexion->real_escape_string($datos['archivo_url'] ?? '');
    $fecha_publicacion = $conexion->real_escape_string($datos['fecha_publicacion'] ?? date('Y-m-d'));
    
    $sql = "INSERT INTO material_clase (id_profesor, seccion, materia, titulo, descripcion, contenido, archivo_url, fecha_publicacion) 
            VALUES ($id_profesor, '$seccion', '$materia', '$titulo', '$descripcion', '$contenido', '$archivo_url', '$fecha_publicacion')";
    
    return $conexion->query($sql);
}

function obtenerMaterialPorSeccion($conexion, $seccion) {
    if (!$conexion) return [];
    
    $seccion = $conexion->real_escape_string($seccion);
    $sql = "SELECT m.*, p.nombre as profesor_nombre FROM material_clase m 
            JOIN profesores p ON m.id_profesor = p.id
            WHERE m.seccion = '$seccion'
            ORDER BY m.fecha_publicacion DESC";
    
    $resultado = $conexion->query($sql);
    $material = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $material[] = $fila;
        }
    }
    
    return $material;
}

function eliminarMaterialClase($conexion, $id) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $sql = "DELETE FROM material_clase WHERE id = $id";
    return $conexion->query($sql);
}

// EXÁMENES
function agregarExamen($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_profesor = (int)$datos['id_profesor'];
    $seccion = $conexion->real_escape_string($datos['seccion']);
    $materia = $conexion->real_escape_string($datos['materia'] ?? '');
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $fecha_examen = $conexion->real_escape_string($datos['fecha_examen']);
    $hora_examen = $conexion->real_escape_string($datos['hora_examen'] ?? '08:00');
    $lugar = $conexion->real_escape_string($datos['lugar'] ?? '');
    $observaciones = $conexion->real_escape_string($datos['observaciones'] ?? '');
    
    $sql = "INSERT INTO examenes (id_profesor, seccion, materia, titulo, descripcion, fecha_examen, hora_examen, lugar, observaciones) 
            VALUES ($id_profesor, '$seccion', '$materia', '$titulo', '$descripcion', '$fecha_examen', '$hora_examen', '$lugar', '$observaciones')";
    
    return $conexion->query($sql);
}

function obtenerExamenesPorSeccion($conexion, $seccion) {
    if (!$conexion) return [];
    
    $seccion = $conexion->real_escape_string($seccion);
    $sql = "SELECT e.*, p.nombre as profesor_nombre FROM examenes e 
            JOIN profesores p ON e.id_profesor = p.id
            WHERE e.seccion = '$seccion'
            ORDER BY e.fecha_examen ASC";
    
    $resultado = $conexion->query($sql);
    $examenes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $examenes[] = $fila;
        }
    }
    
    return $examenes;
}

function eliminarExamen($conexion, $id) {
    if (!$conexion) return false;
    
    $id = (int)$id;
    $sql = "DELETE FROM examenes WHERE id = $id";
    return $conexion->query($sql);
}

// OBTENER TODAS LAS SECCIONES - ELIMINADO (Usada función duplicada abajo)

// OBTENER ESTUDIANTES POR SECCIÓN
function obtenerEstudiantesPorSeccion($conexion, $seccion) {
    if (!$conexion) return [];
    
    $seccion = $conexion->real_escape_string($seccion);
    $sql = "SELECT e.* FROM estudiantes e 
            WHERE e.seccion = '$seccion' AND e.estado = 'Activo'
            ORDER BY e.apellido, e.nombre";
    
    $resultado = $conexion->query($sql);
    $estudiantes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $estudiantes[] = $fila;
        }
    }
    
    return $estudiantes;
}

// SECCIONES DEL PROFESOR
function obtenerSeccionesProfesor($conexion, $id_profesor) {
    if (!$conexion) return [];
    
    $id_profesor = (int)$id_profesor;
    $sql = "SELECT * FROM secciones_profesor 
            WHERE id_profesor = $id_profesor AND estado = 'Activa'
            ORDER BY nombre_seccion ASC";
    
    $resultado = $conexion->query($sql);
    $secciones = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $secciones[] = $fila;
        }
    }
    
    return $secciones;
}

function crearSeccion($conexion, $id_profesor, $nombre_seccion, $grado = '') {
    if (!$conexion) return false;
    
    $id_profesor = (int)$id_profesor;
    $nombre_seccion = $conexion->real_escape_string($nombre_seccion);
    $grado = $conexion->real_escape_string($grado);
    
    $sql = "INSERT INTO secciones_profesor (id_profesor, nombre_seccion, grado) 
            VALUES ($id_profesor, '$nombre_seccion', '$grado')";
    
    return $conexion->query($sql);
}

function obtenerSeccionPorId($conexion, $id_seccion) {
    if (!$conexion) return null;
    
    $id_seccion = (int)$id_seccion;
    $sql = "SELECT * FROM secciones_profesor WHERE id = $id_seccion";
    
    $resultado = $conexion->query($sql);
    return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc() : null;
}

function eliminarSeccion($conexion, $id_seccion) {
    if (!$conexion) return false;
    
    $id_seccion = (int)$id_seccion;
    
    // Primero eliminar actividades de la sección
    @$conexion->query("DELETE FROM actividades_clase WHERE id_seccion = $id_seccion");
    
    // Luego eliminar la sección
    $sql = "DELETE FROM secciones_profesor WHERE id = $id_seccion";
    return $conexion->query($sql);
}

// ACTIVIDADES/LECCIONES
function agregarActividad($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_seccion = (int)$datos['id_seccion'];
    $id_profesor = (int)$datos['id_profesor'];
    $nombre_actividad = $conexion->real_escape_string($datos['nombre_actividad']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $fecha_evaluacion = $conexion->real_escape_string($datos['fecha_evaluacion'] ?? date('Y-m-d'));
    $temas_vistos = $conexion->real_escape_string($datos['temas_vistos'] ?? '');
    
    $sql = "INSERT INTO actividades_clase (id_seccion, id_profesor, nombre_actividad, descripcion, fecha_evaluacion, temas_vistos) 
            VALUES ($id_seccion, $id_profesor, '$nombre_actividad', '$descripcion', '$fecha_evaluacion', '$temas_vistos')";
    
    return $conexion->query($sql);
}

function obtenerActividadesPorSeccion($conexion, $id_seccion) {
    if (!$conexion) return [];
    
    $id_seccion = (int)$id_seccion;
    $sql = "SELECT * FROM actividades_clase 
            WHERE id_seccion = $id_seccion
            ORDER BY fecha_evaluacion DESC, fecha_creacion DESC";
    
    $resultado = $conexion->query($sql);
    $actividades = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $actividades[] = $fila;
        }
    }
    
    return $actividades;
}

function actualizarActividad($conexion, $id_actividad, $datos) {
    if (!$conexion) return false;
    
    $id_actividad = (int)$id_actividad;
    $nombre_actividad = $conexion->real_escape_string($datos['nombre_actividad']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $fecha_evaluacion = $conexion->real_escape_string($datos['fecha_evaluacion'] ?? date('Y-m-d'));
    $temas_vistos = $conexion->real_escape_string($datos['temas_vistos'] ?? '');
    
    $sql = "UPDATE actividades_clase SET 
            nombre_actividad = '$nombre_actividad',
            descripcion = '$descripcion',
            fecha_evaluacion = '$fecha_evaluacion',
            temas_vistos = '$temas_vistos'
            WHERE id = $id_actividad";
    
    return $conexion->query($sql);
}

function eliminarActividad($conexion, $id_actividad) {
    if (!$conexion) return false;
    
    $id_actividad = (int)$id_actividad;
    $sql = "DELETE FROM actividades_clase WHERE id = $id_actividad";
    return $conexion->query($sql);
}

// DISPONIBILIDAD DEL PROFESOR
function agregarDisponibilidad($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_profesor = (int)$datos['id_profesor'];
    $dia_semana = $conexion->real_escape_string($datos['dia_semana']);
    $hora_inicio = $conexion->real_escape_string($datos['hora_inicio']);
    $hora_fin = $conexion->real_escape_string($datos['hora_fin']);
    $tipo_atencion = $conexion->real_escape_string($datos['tipo_atencion'] ?? 'Presencial');
    
    $sql = "INSERT INTO disponibilidad_profesor (id_profesor, dia_semana, hora_inicio, hora_fin, tipo_atencion) 
            VALUES ($id_profesor, '$dia_semana', '$hora_inicio', '$hora_fin', '$tipo_atencion')";
    
    return $conexion->query($sql);
}

function obtenerDisponibilidadProfesor($conexion, $id_profesor) {
    if (!$conexion) return [];
    
    $id_profesor = (int)$id_profesor;
    $sql = "SELECT * FROM disponibilidad_profesor 
            WHERE id_profesor = $id_profesor AND estado = 'Activa'
            ORDER BY FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'), hora_inicio";
    
    $resultado = $conexion->query($sql);
    $disponibilidad = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $disponibilidad[] = $fila;
        }
    }
    
    return $disponibilidad;
}

function eliminarDisponibilidad($conexion, $id_disponibilidad) {
    if (!$conexion) return false;
    
    $id_disponibilidad = (int)$id_disponibilidad;
    $sql = "UPDATE disponibilidad_profesor SET estado = 'Inactiva' WHERE id = $id_disponibilidad";
    return $conexion->query($sql);
}

// REUNIONES
function crearReunion($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_profesor = (int)$datos['id_profesor'];
    $id_representante = (int)$datos['id_representante'];
    $id_estudiante = isset($datos['id_estudiante']) ? (int)$datos['id_estudiante'] : 0;
    $fecha_reunion = $conexion->real_escape_string($datos['fecha_reunion']);
    $hora_reunion = $conexion->real_escape_string($datos['hora_reunion']);
    $tipo_reunion = $conexion->real_escape_string($datos['tipo_reunion'] ?? 'Presencial');
    $asunto = $conexion->real_escape_string($datos['asunto']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    
    $sql = "INSERT INTO reuniones (id_profesor, id_representante, id_estudiante, fecha_reunion, hora_reunion, tipo_reunion, asunto, descripcion) 
            VALUES ($id_profesor, $id_representante, $id_estudiante, '$fecha_reunion', '$hora_reunion', '$tipo_reunion', '$asunto', '$descripcion')";
    
    return $conexion->query($sql);
}

function obtenerReunionesProfesor($conexion, $id_profesor) {
    if (!$conexion) return [];
    
    $id_profesor = (int)$id_profesor;
    $sql = "SELECT r.*, rep.nombre as nombre_representante, est.nombre as nombre_estudiante 
            FROM reuniones r
            LEFT JOIN representantes rep ON r.id_representante = rep.id
            LEFT JOIN estudiantes est ON r.id_estudiante = est.id
            WHERE r.id_profesor = $id_profesor
            ORDER BY r.fecha_reunion DESC, r.hora_reunion DESC";
    
    $resultado = $conexion->query($sql);
    $reuniones = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $reuniones[] = $fila;
        }
    }
    
    return $reuniones;
}

function obtenerReunionesRepresentante($conexion, $id_representante) {
    if (!$conexion) return [];
    
    $id_representante = (int)$id_representante;
    $sql = "SELECT r.*, p.nombre as nombre_profesor, est.nombre as nombre_estudiante 
            FROM reuniones r
            LEFT JOIN profesores p ON r.id_profesor = p.id
            LEFT JOIN estudiantes est ON r.id_estudiante = est.id
            WHERE r.id_representante = $id_representante
            ORDER BY r.fecha_reunion DESC, r.hora_reunion DESC";
    
    $resultado = $conexion->query($sql);
    $reuniones = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $reuniones[] = $fila;
        }
    }
    
    return $reuniones;
}

function actualizarEstadoReunion($conexion, $id_reunion, $estado, $nota = '') {
    if (!$conexion) return false;
    
    $id_reunion = (int)$id_reunion;
    $estado = $conexion->real_escape_string($estado);
    $nota = $conexion->real_escape_string($nota);
    
    $sql = "UPDATE reuniones SET estado = '$estado', nota_profesor = '$nota', fecha_actualizacion = NOW() WHERE id = $id_reunion";
    return $conexion->query($sql);
}

function eliminarReunion($conexion, $id_reunion) {
    if (!$conexion) return false;
    
    $id_reunion = (int)$id_reunion;
    $sql = "DELETE FROM reuniones WHERE id = $id_reunion";
    return $conexion->query($sql);
}

// MENSAJES INTERNOS
function enviarMensaje($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_remitente = (int)$datos['id_remitente'];
    $id_destinatario = (int)$datos['id_destinatario'];
    $tipo_remitente = $conexion->real_escape_string($datos['tipo_remitente']);
    $tipo_destinatario = $conexion->real_escape_string($datos['tipo_destinatario']);
    $asunto = $conexion->real_escape_string($datos['asunto'] ?? '');
    $contenido = $conexion->real_escape_string($datos['contenido']);
    
    $sql = "INSERT INTO mensajes_internos (id_remitente, id_destinatario, tipo_remitente, tipo_destinatario, asunto, contenido) 
            VALUES ($id_remitente, $id_destinatario, '$tipo_remitente', '$tipo_destinatario', '$asunto', '$contenido')";
    
    return $conexion->query($sql);
}

function obtenerMensajesConversacion($conexion, $id_usuario1, $id_usuario2) {
    if (!$conexion) return [];
    
    $id_usuario1 = (int)$id_usuario1;
    $id_usuario2 = (int)$id_usuario2;
    
    $sql = "SELECT m.*, 
            CASE WHEN m.id_remitente = $id_usuario1 THEN 'yo' ELSE 'otro' END as posicion
            FROM mensajes_internos m
            WHERE (m.id_remitente = $id_usuario1 AND m.id_destinatario = $id_usuario2)
            OR (m.id_remitente = $id_usuario2 AND m.id_destinatario = $id_usuario1)
            ORDER BY m.fecha_creacion ASC";
    
    $resultado = $conexion->query($sql);
    $mensajes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $mensajes[] = $fila;
        }
    }
    
    return $mensajes;
}

function obtenerMensajesNoLeidos($conexion, $id_usuario) {
    if (!$conexion) return 0;
    
    $id_usuario = (int)$id_usuario;
    $sql = "SELECT COUNT(*) as total FROM mensajes_internos WHERE id_destinatario = $id_usuario AND leido = FALSE";
    
    $resultado = $conexion->query($sql);
    return ($resultado && $resultado->num_rows > 0) ? $resultado->fetch_assoc()['total'] : 0;
}

function marcarMensajeLeido($conexion, $id_mensaje) {
    if (!$conexion) return false;
    
    $id_mensaje = (int)$id_mensaje;
    $sql = "UPDATE mensajes_internos SET leido = TRUE WHERE id = $id_mensaje";
    return $conexion->query($sql);
}

function eliminarMensaje($conexion, $id_mensaje) {
    if (!$conexion) return false;
    
    $id_mensaje = (int)$id_mensaje;
    $sql = "DELETE FROM mensajes_internos WHERE id = $id_mensaje";
    return $conexion->query($sql);
}

// REPORTES DE CONDUCTA
function crearReporteConducta($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_estudiante = (int)$datos['id_estudiante'];
    $seccion = $conexion->real_escape_string($datos['seccion']);
    $id_profesor = (int)$datos['id_profesor'];
    $tipo_reporte = $conexion->real_escape_string($datos['tipo_reporte']);
    $titulo = $conexion->real_escape_string($datos['titulo']);
    $descripcion = $conexion->real_escape_string($datos['descripcion'] ?? '');
    $fecha_reporte = $conexion->real_escape_string($datos['fecha_reporte'] ?? date('Y-m-d'));
    $hora_reporte = $conexion->real_escape_string($datos['hora_reporte'] ?? date('H:i:s'));
    
    $sql = "INSERT INTO reportes_conducta (id_estudiante, seccion, id_profesor, tipo_reporte, titulo, descripcion, fecha_reporte, hora_reporte)
            VALUES ($id_estudiante, '$seccion', $id_profesor, '$tipo_reporte', '$titulo', '$descripcion', '$fecha_reporte', '$hora_reporte')";
    
    return $conexion->query($sql);
}

function obtenerReportesEstudiante($conexion, $id_estudiante) {
    if (!$conexion) return [];
    
    $id_estudiante = (int)$id_estudiante;
    $sql = "SELECT * FROM reportes_conducta 
            WHERE id_estudiante = $id_estudiante
            ORDER BY fecha_reporte DESC, hora_reporte DESC";
    
    $resultado = $conexion->query($sql);
    $reportes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }
    
    return $reportes;
}

function obtenerReportesSeccion($conexion, $seccion) {
    if (!$conexion) return [];
    
    $seccion = $conexion->real_escape_string($seccion);
    $sql = "SELECT * FROM reportes_conducta 
            WHERE seccion = '$seccion'
            ORDER BY fecha_reporte DESC, hora_reporte DESC";
    
    $resultado = $conexion->query($sql);
    $reportes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }
    
    return $reportes;
}

function obtenerReportesPorProfesor($conexion, $id_profesor) {
    if (!$conexion) return [];
    
    $id_profesor = (int)$id_profesor;
    $sql = "SELECT * FROM reportes_conducta 
            WHERE id_profesor = $id_profesor
            ORDER BY fecha_reporte DESC, hora_reporte DESC";
    
    $resultado = $conexion->query($sql);
    $reportes = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }
    
    return $reportes;
}

function eliminarReporteConducta($conexion, $id_reporte) {
    if (!$conexion) return false;
    
    $id_reporte = (int)$id_reporte;
    @$conexion->query("DELETE FROM firmas_amonestaciones WHERE id_reporte = $id_reporte");
    
    $sql = "DELETE FROM reportes_conducta WHERE id = $id_reporte";
    return $conexion->query($sql);
}

// FIRMAS DIGITALES DE AMONESTACIONES
function registrarFirmaAmonestacion($conexion, $datos) {
    if (!$conexion) return false;
    
    $id_reporte = (int)$datos['id_reporte'];
    $id_representante = (int)$datos['id_representante'];
    $id_estudiante = (int)$datos['id_estudiante'];
    $ip_direccion = $conexion->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
    $navegador = $conexion->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
    $observaciones = $conexion->real_escape_string($datos['observaciones'] ?? '');
    
    $sql = "INSERT INTO firmas_amonestaciones (id_reporte, id_representante, id_estudiante, confirmado, fecha_lectura, ip_direccion, navegador, observaciones)
            VALUES ($id_reporte, $id_representante, $id_estudiante, TRUE, NOW(), '$ip_direccion', '$navegador', '$observaciones')
            ON DUPLICATE KEY UPDATE confirmado = TRUE, fecha_lectura = NOW()";
    
    return $conexion->query($sql);
}

function obtenerFirmasReporte($conexion, $id_reporte) {
    if (!$conexion) return [];
    
    $id_reporte = (int)$id_reporte;
    $sql = "SELECT * FROM firmas_amonestaciones 
            WHERE id_reporte = $id_reporte
            ORDER BY fecha_firma DESC";
    
    $resultado = $conexion->query($sql);
    $firmas = [];
    
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $firmas[] = $fila;
        }
    }
    
    return $firmas;
}

function verificarFirmaRepresentante($conexion, $id_reporte, $id_representante) {
    if (!$conexion) return false;
    
    $id_reporte = (int)$id_reporte;
    $id_representante = (int)$id_representante;
    
    $sql = "SELECT confirmado FROM firmas_amonestaciones 
            WHERE id_reporte = $id_reporte AND id_representante = $id_representante 
            LIMIT 1";
    
    $resultado = $conexion->query($sql);
    return ($resultado && $resultado->num_rows > 0 && $resultado->fetch_assoc()['confirmado']);
}

?>

<?php
/**
 * Módulo de Profesor - Sistema de Gestión Académica, Carga de Notas y Control de Estudiantes a Cargo
 */
session_start();

if (!isset($_SESSION['usuario_logueado'])) {
    $_SESSION['usuario_logueado'] = true;
    $_SESSION['rol'] = 'Profesor';
    $_SESSION['nombre'] = 'Docente de Prueba';
    $_SESSION['username'] = 'jose_paez'; 
}

$rol_actual = $_SESSION['rol'] ?? 'Profesor';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";

$identificador_sesion = '';
$posibles_variables = ['username', 'usuario', 'nombre_usuario', 'user', 'id_usuario', 'id', 'cedula', 'email'];
foreach ($posibles_variables as $var) {
    if (!empty($_SESSION[$var])) {
        $identificador_sesion = $_SESSION[$var];
        break;
    }
}

$usuario_profesor = $identificador_sesion;
$error_db = "";
$status_db = "Desconectado";
$pdo = null;

try {
    $host = 'localhost';
    $dbname = 'escuela_tecnica';
    $user = 'root';
    $pass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_db = "Conectado";

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS asignaciones_docentes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_profesor VARCHAR(100) NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        INDEX(usuario_profesor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS asignaciones_materias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_profesor VARCHAR(100) NOT NULL,
        materia VARCHAR(100) NOT NULL,
        INDEX(usuario_profesor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS evaluaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_profesor VARCHAR(100) NULL,
        creado_por VARCHAR(100) NULL,
        seccion VARCHAR(50) NOT NULL,
        materia VARCHAR(100) NOT NULL DEFAULT 'General',
        lapso INT NOT NULL DEFAULT 1,
        titulo VARCHAR(150) NOT NULL,
        tipo VARCHAR(50) DEFAULT 'Examen',
        ponderacion DECIMAL(5,2) DEFAULT 20.00,
        fecha DATE NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS calificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        evaluacion_id INT NULL,
        estudiante_id INT NOT NULL,
        materia VARCHAR(100) NOT NULL DEFAULT 'General',
        lapso INT NOT NULL DEFAULT 1,
        nota DECIMAL(4,2) NOT NULL DEFAULT 0.00,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_eval_est (evaluacion_id, estudiante_id),
        INDEX(estudiante_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS representantes_estudiantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cedula_representante VARCHAR(20) NOT NULL,
        estudiante_id INT NOT NULL,
        parentesco VARCHAR(50) DEFAULT 'Representante',
        UNIQUE KEY uq_rep_est (cedula_representante, estudiante_id),
        INDEX(estudiante_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch(PDOException $e) {
    $error_db = "Fallo de conexión a la BD: " . $e->getMessage();
}

$mis_secciones = [];
$mis_materias = [];
$estudiantes_por_seccion = [];
$todos_mis_estudiantes = [];
$total_estudiantes = 0;
$evaluaciones_data = [];
$mensaje_exito = "";


$materia_activa = $_GET['materia'] ?? 'Matemática';
$vista_activa = $_GET['seccion'] ?? 'evaluaciones'; 
$lapso_filtro = isset($_GET['lapso']) ? (int)$_GET['lapso'] : 1;
$busqueda_estudiante = trim($_GET['q'] ?? '');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    
    if (isset($_POST['guardar_calificaciones'])) {
        try {
            $evaluacion_id = (int)$_POST['evaluacion_id'];
            $lapso = (int)$_POST['lapso'];
            $materia_post = $_POST['materia'] ?? 'General';
            $notas = $_POST['notas'] ?? [];

            $sql_insert = "INSERT INTO calificaciones (evaluacion_id, estudiante_id, lapso, nota, materia) 
                           VALUES (?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE nota = VALUES(nota), lapso = VALUES(lapso), materia = VALUES(materia)";

            $stmt_calif = $pdo->prepare($sql_insert);
            $estudiante_indiv = isset($_POST['estudiante_id_individual']) ? (int)$_POST['estudiante_id_individual'] : null;

            if ($estudiante_indiv && isset($notas[$estudiante_indiv])) {
                $val_nota = $notas[$estudiante_indiv];
                if ($val_nota !== '') {
                    $stmt_calif->execute([$evaluacion_id, $estudiante_indiv, $lapso, (float)$val_nota, $materia_post]);
                    $mensaje_exito = "¡Calificación de $materia_post guardada de forma individual!";
                }
            } else {
                foreach ($notas as $est_id => $val_nota) {
                    if ($val_nota !== '') {
                        $stmt_calif->execute([$evaluacion_id, (int)$est_id, $lapso, (float)$val_nota, $materia_post]);
                    }
                }
                $mensaje_exito = "¡Calificaciones de $materia_post (Lapso $lapso) registradas/actualizadas correctamente!";
            }
        } catch (PDOException $e) {
            $error_db = "Error al guardar calificaciones: " . $e->getMessage();
        }
    }

    
    if (isset($_POST['guardar_representante'])) {
        try {
            $estudiante_id = (int)$_POST['estudiante_id'];
            $cedula_rep = trim($_POST['cedula_representante']);
            $parentesco = trim($_POST['parentesco'] ?? 'Representante');

            if (!empty($cedula_rep) && $estudiante_id > 0) {
                $stmt_rep = $pdo->prepare("
                    INSERT INTO representantes_estudiantes (cedula_representante, estudiante_id, parentesco)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE cedula_representante = VALUES(cedula_representante), parentesco = VALUES(parentesco)
                ");
                $stmt_rep->execute([$cedula_rep, $estudiante_id, $parentesco]);
                $mensaje_exito = "Representante asignado exitosamente al estudiante.";
            }
        } catch (PDOException $e) {
            $error_db = "Error al vincular representante: " . $e->getMessage();
        }
    }

    
    if (isset($_POST['cambiar_seccion_estudiante'])) {
        try {
            $estudiante_id = (int)$_POST['estudiante_id'];
            $nueva_seccion = trim($_POST['nueva_seccion']);
            $nuevo_nivel = trim($_POST['nuevo_nivel']);

            if ($estudiante_id > 0 && !empty($nueva_seccion)) {
                $stmt_sec = $pdo->prepare("UPDATE estudiantes SET seccion = ?, nivel_academico = ? WHERE id = ?");
                $stmt_sec->execute([$nueva_seccion, $nuevo_nivel, $estudiante_id]);
                $mensaje_exito = "Estudiante reasignado a la Sección $nueva_seccion ($nuevo_nivel) con éxito.";
            }
        } catch (PDOException $e) {
            $error_db = "Error al cambiar de sección al estudiante: " . $e->getMessage();
        }
    }

    
    if (isset($_POST['guardar_evaluacion'])) {
        try {
            $lapso_ev = isset($_POST['lapso']) ? (int)$_POST['lapso'] : 1;
            $materia_ev = $_POST['materia'] ?? 'General';
            $fecha_ev = !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
            $titulo_ev = htmlspecialchars($_POST['titulo']);
            $seccion_ev = htmlspecialchars($_POST['seccion']);
            $tipo_ev = htmlspecialchars($_POST['tipo']);
            $ponderacion_ev = (float)$_POST['ponderacion'];

            $stmt = $pdo->prepare("INSERT INTO evaluaciones (titulo, seccion, materia, tipo, ponderacion, fecha, creado_por, usuario_profesor, lapso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo_ev, $seccion_ev, $materia_ev, $tipo_ev, $ponderacion_ev, $fecha_ev, $usuario_profesor, $usuario_profesor, $lapso_ev]);

            $mensaje_exito = "Evaluación de $materia_ev para Lapso $lapso_ev creada con éxito.";
        } catch (PDOException $e) {
            $error_db = "Error al crear evaluación: " . $e->getMessage();
        }
    } 

    
    if (isset($_POST['eliminar_evaluacion'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM evaluaciones WHERE id = ? AND (creado_por = ? OR usuario_profesor = ?)");
            $stmt->execute([$_POST['id_evaluacion'], $usuario_profesor, $usuario_profesor]);
            $mensaje_exito = "Evaluación eliminada correctamente.";
        } catch (PDOException $e) {
            $error_db = "Error al eliminar evaluación: " . $e->getMessage();
        }
    }
}


if ($pdo && !empty($usuario_profesor)) {
    try {
        $stmt_u = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR id = ? OR cedula = ? OR email = ? LIMIT 1");
        $id_val = is_numeric($usuario_profesor) ? (int)$usuario_profesor : 0;
        $stmt_u->execute([$usuario_profesor, $id_val, $usuario_profesor, $usuario_profesor]);
        $real_username = $stmt_u->fetchColumn();
        if ($real_username) { $usuario_profesor = $real_username; }

        
        $stmt = $pdo->prepare("SELECT seccion FROM asignaciones_docentes WHERE LOWER(TRIM(usuario_profesor)) = LOWER(TRIM(?)) ORDER BY seccion ASC");
        $stmt->execute([$usuario_profesor]);
        $mis_secciones = array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN));

        
        $stmt_mat = $pdo->prepare("SELECT materia FROM asignaciones_materias WHERE LOWER(TRIM(usuario_profesor)) = LOWER(TRIM(?)) ORDER BY materia ASC");
        $stmt_mat->execute([$usuario_profesor]);
        $mis_materias = array_map('trim', $stmt_mat->fetchAll(PDO::FETCH_COLUMN));

        
        if (empty($mis_secciones)) { $mis_secciones = ['1A', '2A']; }
        if (empty($mis_materias)) { 
            if ($usuario_profesor == 'jose_paez') {
                $mis_materias = ['Matemática', 'Castellano', 'Biología'];
            } else {
                $mis_materias = ['Matemática', 'General'];
            }
        }

        if (!isset($_GET['materia']) && !empty($mis_materias)) {
            $materia_activa = $mis_materias[0];
        }

        
        if (!empty($mis_secciones)) {
            $ids_procesados = [];
            foreach ($mis_secciones as $seccion) {
                preg_match('/^(\d+)?[-\s]*([A-Za-z0-9]+)/', trim($seccion), $coincidencias);
                $seccion_base = $coincidencias[0] ?? $seccion;
                $letra_sola = $coincidencias[2] ?? $seccion;

                $stmt_est = $pdo->prepare("
                    SELECT DISTINCT e.id, e.cedula, e.nombre, e.apellido, e.email, e.nivel_academico, e.seccion,
                           r.cedula_representante, r.parentesco
                    FROM estudiantes e
                    LEFT JOIN representantes_estudiantes r ON e.id = r.estudiante_id
                    LEFT JOIN asignaciones_estudiantes_materias aem ON e.id = aem.estudiante_id
                    WHERE (e.estado = 'Activo' OR e.estado IS NULL OR e.estado = '')
                      AND (
                           TRIM(LOWER(e.seccion)) = TRIM(LOWER(:seccion_exacta))
                           OR TRIM(LOWER(e.seccion)) = TRIM(LOWER(:seccion_base))
                           OR TRIM(LOWER(e.seccion)) = TRIM(LOWER(:letra_sola))
                           OR e.seccion LIKE CONCAT('%', :seccion_base, '%')
                      )
                    ORDER BY e.apellido ASC, e.nombre ASC
                ");
                
                $stmt_est->execute([
                    ':seccion_exacta' => $seccion,
                    ':seccion_base'   => $seccion_base,
                    ':letra_sola'     => $letra_sola
                ]);

                $alumnos_seccion = $stmt_est->fetchAll(PDO::FETCH_ASSOC);
                $estudiantes_por_seccion[$seccion] = $alumnos_seccion;

                
                foreach ($alumnos_seccion as $alumno) {
                    if (!in_array($alumno['id'], $ids_procesados)) {
                        $ids_procesados[] = $alumno['id'];
                        $todos_mis_estudiantes[] = $alumno;
                    }
                }
            }
            $total_estudiantes = count($todos_mis_estudiantes);
        }

        
        $stmt_ev = $pdo->prepare("SELECT * FROM evaluaciones WHERE creado_por = ? OR usuario_profesor = ? ORDER BY fecha ASC");
        $stmt_ev->execute([$usuario_profesor, $usuario_profesor]);
        $evaluaciones_data = $stmt_ev->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_db = "Error al consultar datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente y Carga de Notas - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .slide-up { animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideUp { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    
    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30 text-slate-950 font-bold shrink-0">
                    <i class="fa-solid fa-graduation-cap text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xs font-bold leading-tight uppercase tracking-wider text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-amber-400 font-medium">Pedro Garcia Leal</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Panel Docente</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors text-sm font-medium">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>
            <a href="profesor.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-950 font-bold rounded-xl shadow-md text-sm">
                <i class="fa-solid fa-pen-to-square w-5"></i> Carga de Notas & Materias
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 rounded-full bg-amber-500 text-slate-950 font-black flex items-center justify-center">
                    <?= mb_substr($_SESSION['nombre'] ?? 'P', 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($_SESSION['nombre'] ?? $usuario_profesor); ?></p>
                    <p class="text-xs text-amber-400 font-medium">@<?= htmlspecialchars($usuario_profesor) ?></p>
                </div>
            </div>
        </div>
    </aside>

    
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Gestión Académica por Materia y Sección</h2>
                    <p class="text-xs text-slate-500">Carga de calificaciones y listado de alumnos asignados en sus secciones</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">
                    <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($status_db) ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-6">

                <?php if ($mensaje_exito): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl flex items-center gap-3 shadow-sm slide-up">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($mensaje_exito); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_db): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl flex items-center gap-3 shadow-sm slide-up">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-2xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($error_db); ?></span>
                    </div>
                <?php endif; ?>

                
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm slide-up">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-book-bookmark text-amber-500"></i> Seleccionar Asignatura Activa:
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($mis_materias as $mat): ?>
                            <a href="?materia=<?= urlencode($mat) ?>&seccion=<?= urlencode($vista_activa) ?>&lapso=<?= $lapso_filtro ?>" 
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all border flex items-center gap-2 <?= $materia_activa === $mat ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                                <i class="fa-solid fa-book"></i>
                                <?= htmlspecialchars($mat) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-3 flex flex-wrap items-center justify-between gap-4 slide-up">
                    
                    <div class="flex flex-wrap gap-2">
                        <a href="?materia=<?= urlencode($materia_activa) ?>&seccion=evaluaciones&lapso=<?= $lapso_filtro ?>" 
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($vista_activa === 'evaluaciones') ? 'bg-amber-500 text-slate-950 shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                            <i class="fa-solid fa-list-check mr-1"></i> Evaluaciones de <?= htmlspecialchars($materia_activa) ?>
                        </a>

                        
                        <a href="?materia=<?= urlencode($materia_activa) ?>&seccion=mis_estudiantes&lapso=<?= $lapso_filtro ?>" 
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 <?= ($vista_activa === 'mis_estudiantes') ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                            <i class="fa-solid fa-users"></i> Mis Estudiantes (<?= $total_estudiantes ?>)
                        </a>

                        <div class="h-6 w-px bg-slate-200 my-auto hidden sm:block"></div>

                        <?php foreach ($mis_secciones as $sec): ?>
                            <a href="?materia=<?= urlencode($materia_activa) ?>&seccion=<?= urlencode($sec) ?>&lapso=<?= $lapso_filtro ?>" 
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($vista_activa === $sec) ? 'bg-amber-500 text-slate-950 shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                Sección <?= htmlspecialchars($sec) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    
                    <?php if ($vista_activa !== 'mis_estudiantes'): ?>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500 uppercase">Lapso:</span>
                            <?php for($i=1; $i<=3; $i++): ?>
                                <a href="?materia=<?= urlencode($materia_activa) ?>&seccion=<?= urlencode($vista_activa) ?>&lapso=<?= $i ?>" 
                                   class="px-3 py-1.5 rounded-lg text-xs font-black transition-all <?= ($lapso_filtro === $i) ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    Lapso <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

                
                <?php if ($vista_activa === 'mis_estudiantes'): ?>
                    <?php 
                        
                        $estudiantes_filtrados_prof = array_filter($todos_mis_estudiantes, function($est) use ($busqueda_estudiante) {
                            if (empty($busqueda_estudiante)) return true;
                            $q = mb_strtolower($busqueda_estudiante);
                            return (
                                mb_strpos(mb_strtolower($est['nombre']), $q) !== false ||
                                mb_strpos(mb_strtolower($est['apellido']), $q) !== false ||
                                mb_strpos(mb_strtolower($est['cedula']), $q) !== false
                            );
                        });
                    ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                        <div class="p-6 border-b border-slate-200 bg-slate-50 flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i class="fa-solid fa-users text-indigo-600"></i> Estudiantes Asignados a mis Secciones
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Listado general de alumnos matriculados en sus secciones a cargo: <span class="font-bold text-indigo-600"><?= implode(', ', $mis_secciones) ?></span>
                                </p>
                            </div>
                            
                            <form method="GET" class="flex gap-2 w-full md:w-auto">
                                <input type="hidden" name="materia" value="<?= htmlspecialchars($materia_activa) ?>">
                                <input type="hidden" name="seccion" value="mis_estudiantes">
                                <div class="relative flex-1 md:w-64">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                    </span>
                                    <input type="text" name="q" value="<?= htmlspecialchars($busqueda_estudiante) ?>" placeholder="Buscar por alumno o cédula..." class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                </div>
                                <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-slate-800 transition-all">Filtrar</button>
                            </form>
                        </div>

                        <?php if (empty($estudiantes_filtrados_prof)): ?>
                            <div class="p-12 text-center text-slate-400">
                                <i class="fa-solid fa-user-slash text-4xl mb-3 text-indigo-400"></i>
                                <p class="font-semibold text-slate-700 text-base">No se encontraron estudiantes asignados en sus secciones.</p>
                                <p class="text-xs mt-1 text-slate-500">Asegúrese de que el módulo de coordinación haya asignado alumnos a la(s) sección(es): <?= htmlspecialchars(implode(', ', $mis_secciones)) ?>.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-bold">
                                        <tr>
                                            <th class="px-6 py-3">Estudiante</th>
                                            <th class="px-6 py-3">Cédula</th>
                                            <th class="px-6 py-3">Nivel Académico</th>
                                            <th class="px-6 py-3">Sección</th>
                                            <th class="px-6 py-3">Representante Legal</th>
                                            <th class="px-6 py-3 text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php foreach ($estudiantes_filtrados_prof as $est): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4 font-semibold text-slate-800">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center shrink-0 border border-indigo-200 text-xs">
                                                            <?= mb_substr($est['nombre'], 0, 1) . mb_substr($est['apellido'], 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) ?></p>
                                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($est['email'] ?? 'Sin correo registrado') ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-slate-600 text-xs font-mono font-medium">
                                                    <?= htmlspecialchars($est['cedula']) ?>
                                                </td>
                                                <td class="px-6 py-4 text-xs font-medium text-slate-700">
                                                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg font-semibold text-slate-700">
                                                        <?= htmlspecialchars($est['nivel_academico'] ?? '2do Año') ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                        <i class="fa-solid fa-school"></i> Sección <?= htmlspecialchars($est['seccion']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-xs">
                                                    <?php if (!empty($est['cedula_representante'])): ?>
                                                        <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg font-bold border border-emerald-200">
                                                            <i class="fa-solid fa-user-shield"></i>
                                                            <?= htmlspecialchars($est['cedula_representante']) ?> (<?= htmlspecialchars($est['parentesco'] ?? 'Rep.') ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 italic">Sin representante</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <a href="?materia=<?= urlencode($materia_activa) ?>&seccion=<?= urlencode($est['seccion']) ?>&lapso=<?= $lapso_filtro ?>" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-3 py-1.5 rounded-xl text-xs shadow-sm transition-all inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-pen-to-square"></i> Cargar Nota
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                
                <?php elseif ($vista_activa !== 'evaluaciones' && isset($estudiantes_por_seccion[$vista_activa])): ?>
                    <?php 
                        $stmt_ev_sec = $pdo->prepare("
                            SELECT * FROM evaluaciones 
                            WHERE TRIM(LOWER(seccion)) = TRIM(LOWER(?)) 
                              AND TRIM(LOWER(materia)) = TRIM(LOWER(?))
                              AND (lapso = ? OR lapso IS NULL OR lapso = 0) 
                            ORDER BY fecha ASC
                        ");
                        $stmt_ev_sec->execute([$vista_activa, $materia_activa, $lapso_filtro]);
                        $evaluaciones_seccion = $stmt_ev_sec->fetchAll(PDO::FETCH_ASSOC);

                        $calificaciones_cargadas = [];
                        if(!empty($evaluaciones_seccion)) {
                            $ids_ev = array_column($evaluaciones_seccion, 'id');
                            $in_clause = implode(',', array_fill(0, count($ids_ev), '?'));

                            $stmt_calif_exist = $pdo->prepare("SELECT evaluacion_id, estudiante_id, nota FROM calificaciones WHERE evaluacion_id IN ($in_clause) AND lapso = ?");
                            $params = array_merge($ids_ev, [$lapso_filtro]);
                            
                            $stmt_calif_exist->execute($params);
                            while($row = $stmt_calif_exist->fetch(PDO::FETCH_ASSOC)) {
                                $calificaciones_cargadas[$row['evaluacion_id']][$row['estudiante_id']] = $row['nota'];
                            }
                        }
                    ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                        <div class="p-6 border-b border-slate-200 bg-slate-50 flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">
                                    Cargar Calificaciones: <span class="text-blue-600"><?= htmlspecialchars($materia_activa) ?></span> - Sección <?= htmlspecialchars($vista_activa) ?> (Lapso <?= $lapso_filtro ?>)
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Estudiantes en esta sección: <span class="font-bold text-amber-600"><?= count($estudiantes_por_seccion[$vista_activa]) ?></span>
                                </p>
                            </div>
                        </div>

                        <?php if (empty($evaluaciones_seccion)): ?>
                            <div class="p-10 text-center text-slate-400">
                                <i class="fa-solid fa-triangle-exclamation text-3xl mb-2 text-amber-500"></i>
                                <p class="font-medium text-slate-700">No hay evaluaciones registradas para <b><?= htmlspecialchars($materia_activa) ?></b> en la Sección <?= htmlspecialchars($vista_activa) ?> (Lapso <?= $lapso_filtro ?>).</p>
                                <p class="text-xs mt-1 text-slate-500">Diríjase a la pestaña "Evaluaciones de <?= htmlspecialchars($materia_activa) ?>" para agregar una nueva actividad evaluativa.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($evaluaciones_seccion as $ev): ?>
                                <form method="POST" class="p-6 border-b border-slate-200 bg-slate-50/30 space-y-4">
                                    <input type="hidden" name="evaluacion_id" value="<?= $ev['id'] ?>">
                                    <input type="hidden" name="lapso" value="<?= $lapso_filtro ?>">
                                    <input type="hidden" name="materia" value="<?= htmlspecialchars($materia_activa) ?>">
                                    
                                    <div class="flex flex-wrap items-center justify-between bg-amber-50 border border-amber-200 p-3 rounded-xl gap-2">
                                        <div>
                                            <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">
                                                Actividad: <?= htmlspecialchars($ev['tipo']) ?> | Ponderación: <?= $ev['ponderacion'] ?>% | Asignatura: <?= htmlspecialchars($ev['materia']) ?>
                                            </span>
                                            <h4 class="font-bold text-slate-800 text-base"><?= htmlspecialchars($ev['titulo']) ?></h4>
                                        </div>
                                        <button type="submit" name="guardar_calificaciones" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs shadow-md transition-all">
                                            <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Calificaciones
                                        </button>
                                    </div>

                                    <?php if (empty($estudiantes_por_seccion[$vista_activa])): ?>
                                        <div class="p-6 text-center text-slate-400 bg-white rounded-xl border border-dashed border-slate-200">
                                            <p class="text-sm font-semibold">No se encontraron estudiantes registrados para esta sección.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-sm">
                                                <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-bold">
                                                    <tr>
                                                        <th class="px-4 py-2">Estudiante</th>
                                                        <th class="px-4 py-2">Cédula</th>
                                                        <th class="px-4 py-2">Nivel / Sección</th>
                                                        <th class="px-4 py-2">Representante</th>
                                                        <th class="px-4 py-2 text-center w-48">Nota (0-20) y Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                    <?php foreach ($estudiantes_por_seccion[$vista_activa] as $est): ?>
                                                        <?php $nota_val = $calificaciones_cargadas[$ev['id']][$est['id']] ?? ''; ?>
                                                        <tr class="hover:bg-slate-50 transition-colors">
                                                            <td class="px-4 py-2 font-semibold text-slate-800">
                                                                <i class="fa-solid fa-user-graduate text-amber-500 mr-2 text-xs"></i>
                                                                <?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) ?>
                                                            </td>
                                                            <td class="px-4 py-2 text-slate-500 text-xs font-mono">
                                                                <?= htmlspecialchars($est['cedula']) ?>
                                                            </td>
                                                            <td class="px-4 py-2 text-slate-500 text-xs">
                                                                <div class="flex items-center gap-1.5">
                                                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-bold">
                                                                        <?= htmlspecialchars(($est['nivel_academico'] ?? 'Grado') . ' - ' . $est['seccion']) ?>
                                                                    </span>
                                                                    <button type="button" onclick="abrirModalSeccion(<?= $est['id'] ?>, '<?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['seccion'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['nivel_academico'] ?? '2do Año', ENT_QUOTES) ?>')" class="text-xs text-blue-600 hover:text-blue-800 font-semibold" title="Cambiar sección/materia">
                                                                        <i class="fa-solid fa-pen"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-2 text-xs">
                                                                <?php if (!empty($est['cedula_representante'])): ?>
                                                                    <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg font-bold border border-emerald-200">
                                                                        <i class="fa-solid fa-user-shield"></i>
                                                                        <?= htmlspecialchars($est['cedula_representante']) ?> (<?= htmlspecialchars($est['parentesco'] ?? 'Rep.') ?>)
                                                                    </span>
                                                                    <button type="button" onclick="abrirModalRepresentante(<?= $est['id'] ?>, '<?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['cedula_representante'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['parentesco'] ?? 'Padre/Madre', ENT_QUOTES) ?>')" class="ml-1 text-slate-400 hover:text-amber-600" title="Editar Representante">
                                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button type="button" onclick="abrirModalRepresentante(<?= $est['id'] ?>, '<?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES) ?>', '', 'Padre/Madre')" class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-800 px-2.5 py-1 rounded-lg font-bold text-xs border border-amber-300 transition-colors">
                                                                        <i class="fa-solid fa-user-plus text-amber-600"></i> Asignar Rep.
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="px-4 py-2 text-center">
                                                                <div class="flex items-center justify-center gap-1.5">
                                                                    <input type="number" step="0.1" min="0" max="20" name="notas[<?= $est['id'] ?>]" value="<?= htmlspecialchars($nota_val) ?>" placeholder="0.0" class="w-20 text-center px-2 py-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-bold bg-white text-sm">
                                                                    <button type="submit" name="guardar_calificaciones" onclick="setIndividualStudent(this, <?= $est['id'] ?>)" class="bg-amber-500 hover:bg-amber-600 text-slate-950 p-1.5 rounded-lg text-xs font-bold transition-all shadow-sm" title="Guardar esta nota de forma individual">
                                                                        <i class="fa-solid fa-check"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 slide-up">
                        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg">Evaluaciones Registradas - <span class="text-blue-600"><?= htmlspecialchars($materia_activa) ?></span></h3>
                                <p class="text-xs text-slate-500">Planifique las actividades separadas por materia, sección y lapso</p>
                            </div>
                            <button onclick="document.getElementById('modalEvaluacion').classList.remove('hidden'); document.getElementById('modalEvaluacion').classList.add('flex');" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-2.5 px-5 rounded-xl text-xs shadow-md flex items-center gap-2">
                                <i class="fa-solid fa-plus"></i> Nueva Evaluación
                            </button>
                        </div>

                        <?php 
                            $evaluaciones_filtradas = array_filter($evaluaciones_data, function($ev) use ($materia_activa) {
                                return ($ev['materia'] ?? 'General') === $materia_activa;
                            });
                        ?>

                        <?php if (empty($evaluaciones_filtradas)): ?>
                            <div class="p-10 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-amber-500"></i>
                                <p class="font-medium text-slate-700">No hay evaluaciones registradas para la asignatura <b><?= htmlspecialchars($materia_activa) ?></b>.</p>
                                <p class="text-xs mt-1 text-slate-500">Haga clic en "+ Nueva Evaluación" para crear actividades evaluativas.</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <?php foreach ($evaluaciones_filtradas as $ev): ?>
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 relative flex flex-col justify-between hover:border-amber-400 transition-all">
                                        <div>
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-xs font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md">Lapso <?= $ev['lapso'] ?? 1 ?></span>
                                                <span class="text-xs font-bold text-slate-600"><?= $ev['ponderacion'] ?>%</span>
                                            </div>
                                            <h4 class="font-bold text-slate-800 text-base"><?= htmlspecialchars($ev['titulo']) ?></h4>
                                            <p class="text-xs text-slate-500 mt-1">
                                                Sección: <b><?= htmlspecialchars($ev['seccion']) ?></b> | Tipo: <b><?= htmlspecialchars($ev['tipo']) ?></b>
                                            </p>
                                            <p class="text-[11px] text-blue-600 font-semibold mt-1">
                                                <i class="fa-solid fa-book"></i> Asignatura: <?= htmlspecialchars($ev['materia'] ?? 'General') ?>
                                            </p>
                                        </div>
                                        <form method="POST" class="mt-4 text-right" onsubmit="return confirm('¿Desea eliminar esta evaluación?');">
                                            <input type="hidden" name="id_evaluacion" value="<?= $ev['id'] ?>">
                                            <button type="submit" name="eliminar_evaluacion" class="text-red-500 text-xs hover:underline font-bold">
                                                <i class="fa-regular fa-trash-can mr-1"></i>Eliminar
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    
    <div id="modalRepresentante" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden p-6 slide-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2 text-amber-600 font-bold">
                    <i class="fa-solid fa-user-shield text-lg"></i>
                    <h3 class="text-base text-slate-800">Asignar Representante</h3>
                </div>
                <button type="button" onclick="cerrarModalRepresentante()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="estudiante_id" id="modal_estudiante_id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Estudiante Seleccionado:</label>
                    <input type="text" id="modal_estudiante_nombre" readonly class="w-full px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-bold text-slate-700 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cédula del Representante *</label>
                    <input type="text" name="cedula_representante" id="modal_cedula_rep" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-amber-500" required placeholder="Ej: V-12345678">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Parentesco</label>
                    <select name="parentesco" id="modal_parentesco" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium">
                        <option value="Padre/Madre">Padre / Madre</option>
                        <option value="Tutor Legal">Tutor Legal</option>
                        <option value="Abuelo/a">Abuelo / Abuela</option>
                        <option value="Tío/a">Tío / Tía</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalRepresentante()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Cancelar</button>
                    <button type="submit" name="guardar_representante" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold shadow-md transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Representante
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalSeccion" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden p-6 slide-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2 text-blue-600 font-bold">
                    <i class="fa-solid fa-school text-lg"></i>
                    <h3 class="text-base text-slate-800">Cambiar Sección de Estudiante</h3>
                </div>
                <button type="button" onclick="cerrarModalSeccion()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="estudiante_id" id="modal_sec_estudiante_id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Estudiante:</label>
                    <input type="text" id="modal_sec_estudiante_nombre" readonly class="w-full px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-bold text-slate-700 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nueva Sección *</label>
                    <input type="text" name="nueva_seccion" id="modal_sec_seccion" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-bold focus:ring-2 focus:ring-blue-500" required placeholder="Ej: 2A o 1B">
                    <p class="text-[11px] text-slate-400 mt-1">Escriba el código de la sección (Ej: 2A, 1A, 3B).</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nivel Académico / Año</label>
                    <select name="nuevo_nivel" id="modal_sec_nivel" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium">
                        <option value="1er Año">1er Año</option>
                        <option value="2do Año">2do Año</option>
                        <option value="3er Año">3er Año</option>
                        <option value="4to Año">4to Año</option>
                        <option value="5to Año">5to Año</option>
                        <option value="6to Año">6to Año</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalSeccion()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Cancelar</button>
                    <button type="submit" name="cambiar_seccion_estudiante" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Actualizar Sección
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalEvaluacion" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden p-6 slide-up">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Programar Evaluación</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Asignatura</label>
                    <select name="materia" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm bg-slate-50 font-semibold" required>
                        <?php foreach($mis_materias as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $materia_activa === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Título de la Actividad</label>
                    <input type="text" name="titulo" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required placeholder="Ej: Taller de Algoritmos">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Sección</label>
                        <select name="seccion" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required>
                            <?php foreach($mis_secciones as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>" <?= $vista_activa === $sec ? 'selected' : '' ?>>Sección <?= htmlspecialchars($sec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Lapso Escolar</label>
                        <select name="lapso" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required>
                            <option value="1" <?= $lapso_filtro === 1 ? 'selected' : '' ?>>Lapso 1</option>
                            <option value="2" <?= $lapso_filtro === 2 ? 'selected' : '' ?>>Lapso 2</option>
                            <option value="3" <?= $lapso_filtro === 3 ? 'selected' : '' ?>>Lapso 3</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipo</label>
                        <select name="tipo" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required>
                            <option value="Examen">Examen</option>
                            <option value="Taller">Taller</option>
                            <option value="Exposición">Exposición</option>
                            <option value="Proyecto">Proyecto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ponderación (%)</label>
                        <input type="number" name="ponderacion" min="1" max="100" value="20" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm" required>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modalEvaluacion').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold">Cancelar</button>
                    <button type="submit" name="guardar_evaluacion" class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-bold shadow-md">Guardar Evaluación</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
        }

        function setIndividualStudent(btn, studentId) {
            const form = btn.closest('form');
            let hiddenInput = form.querySelector('input[name="estudiante_id_individual"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'estudiante_id_individual';
                form.appendChild(hiddenInput);
            }
            hiddenInput.value = studentId;
        }

        function abrirModalRepresentante(idEstudiante, nombreEstudiante, cedulaRep, parentesco) {
            document.getElementById('modal_estudiante_id').value = idEstudiante;
            document.getElementById('modal_estudiante_nombre').value = nombreEstudiante;
            document.getElementById('modal_cedula_rep').value = cedulaRep || '';
            if (parentesco) {
                document.getElementById('modal_parentesco').value = parentesco;
            }
            const modal = document.getElementById('modalRepresentante');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalRepresentante() {
            const modal = document.getElementById('modalRepresentante');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalSeccion(idEstudiante, nombreEstudiante, seccionActual, nivelActual) {
            document.getElementById('modal_sec_estudiante_id').value = idEstudiante;
            document.getElementById('modal_sec_estudiante_nombre').value = nombreEstudiante;
            document.getElementById('modal_sec_seccion').value = seccionActual || '';
            if (nivelActual) {
                document.getElementById('modal_sec_nivel').value = nivelActual;
            }
            const modal = document.getElementById('modalSeccion');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalSeccion() {
            const modal = document.getElementById('modalSeccion');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
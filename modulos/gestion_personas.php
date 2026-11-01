<?php
/**
 * Módulo de Gestión General de Personas y Asignaciones Académicas
 * Permite listar, filtrar, cambiar de sección, nivel y gestionar asignaturas de los estudiantes y usuarios.
 */
session_start();

if (!isset($_SESSION['usuario_logueado'])) {
    $_SESSION['usuario_logueado'] = true;
    $_SESSION['rol'] = 'Coordinador';
    $_SESSION['nombre'] = 'Coordinador General';
    $_SESSION['username'] = 'admin_general'; 
}

$rol_actual = $_SESSION['rol'] ?? 'Coordinador';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";

$error_db = "";
$status_db = "Desconectado";
$pdo = null;
$mensaje_exito = "";

try {
    $host = 'localhost';
    $dbname = 'escuela_tecnica';
    $user = 'root';
    $pass = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_db = "Conectado";

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS estudiantes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cedula VARCHAR(20) NOT NULL UNIQUE,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        email VARCHAR(150) NULL,
        nivel_academico VARCHAR(50) DEFAULT '2do Año',
        seccion VARCHAR(50) DEFAULT '2A',
        estado VARCHAR(50) DEFAULT 'Activo',
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS asignaciones_estudiantes_materias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        materia VARCHAR(100) NOT NULL,
        UNIQUE KEY uq_est_mat (estudiante_id, materia),
        INDEX(estudiante_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS materias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        codigo VARCHAR(20) NULL,
        descripcion VARCHAR(255) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch(PDOException $e) {
    $error_db = "Fallo de conexión a la BD: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    
    if (isset($_POST['actualizar_seccion_nivel'])) {
        try {
            $estudiante_id = (int)$_POST['estudiante_id'];
            $nueva_seccion = trim($_POST['nueva_seccion']);
            $nuevo_nivel = trim($_POST['nuevo_nivel']);
            $nuevo_estado = trim($_POST['estado']);

            if ($estudiante_id > 0) {
                $stmt = $pdo->prepare("UPDATE estudiantes SET seccion = ?, nivel_academico = ?, estado = ? WHERE id = ?");
                $stmt->execute([$nueva_seccion, $nuevo_nivel, $nuevo_estado, $estudiante_id]);
                $mensaje_exito = "¡Datos de sección y nivel académico actualizados con éxito!";
            }
        } catch (PDOException $e) {
            $error_db = "Error al actualizar sección: " . $e->getMessage();
        }
    }

    
    if (isset($_POST['gestionar_materias_estudiante'])) {
        try {
            $estudiante_id = (int)$_POST['estudiante_id'];
            $materias_seleccionadas = $_POST['materias'] ?? [];

            
            $stmt_del = $pdo->prepare("DELETE FROM asignaciones_estudiantes_materias WHERE estudiante_id = ?");
            $stmt_del->execute([$estudiante_id]);

            
            if (!empty($materias_seleccionadas)) {
                $stmt_ins = $pdo->prepare("INSERT INTO asignaciones_estudiantes_materias (estudiante_id, materia) VALUES (?, ?)");
                foreach ($materias_seleccionadas as $mat) {
                    $stmt_ins->execute([$estudiante_id, trim($mat)]);
                }
            }
            $mensaje_exito = "¡Asignaturas del estudiante actualizadas correctamente!";
        } catch (PDOException $e) {
            $error_db = "Error al actualizar asignaturas: " . $e->getMessage();
        }
    }

    
    if (isset($_POST['registrar_persona'])) {
        try {
            $cedula = trim($_POST['cedula']);
            $nombre = trim($_POST['nombre']);
            $apellido = trim($_POST['apellido']);
            $email = trim($_POST['email']);
            $nivel = trim($_POST['nivel_academico']);
            $seccion = trim($_POST['seccion']);

            if (!empty($cedula) && !empty($nombre) && !empty($apellido)) {
                $stmt = $pdo->prepare("INSERT INTO estudiantes (cedula, nombre, apellido, email, nivel_academico, seccion) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido), nivel_academico=VALUES(nivel_academico), seccion=VALUES(seccion)");
                $stmt->execute([$cedula, $nombre, $apellido, $email, $nivel, $seccion]);
                $mensaje_exito = "Persona / Estudiante registrado en el sistema exitosamente.";
            } else {
                $error_db = "Debe completar los campos obligatorios (Cédula, Nombre, Apellido).";
            }
        } catch (PDOException $e) {
            $error_db = "Error al registrar persona: " . $e->getMessage();
        }
    }
}


$busqueda = trim($_GET['q'] ?? '');
$filtro_seccion = trim($_GET['seccion'] ?? 'todos');
$filtro_nivel = trim($_GET['nivel'] ?? 'todos');

$estudiantes = [];
$total_personas = 0;

if ($pdo) {
    try {
        $sql = "SELECT e.* FROM estudiantes e WHERE 1=1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula LIKE ? OR e.email LIKE ?)";
            $param_busq = "%$busqueda%";
            array_push($params, $param_busq, $param_busq, $param_busq, $param_busq);
        }

        if ($filtro_seccion !== 'todos') {
            $sql .= " AND e.seccion = ?";
            array_push($params, $filtro_seccion);
        }

        if ($filtro_nivel !== 'todos') {
            $sql .= " AND e.nivel_academico = ?";
            array_push($params, $filtro_nivel);
        }

        $sql .= " ORDER BY e.apellido ASC, e.nombre ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_personas = count($estudiantes);

        
        $secciones_db = $pdo->query("SELECT DISTINCT seccion FROM estudiantes WHERE seccion IS NOT NULL AND seccion != '' ORDER BY seccion ASC")->fetchAll(PDO::FETCH_COLUMN);
        
        
        $materias_por_estudiante = [];
        $res_mat = $pdo->query("SELECT estudiante_id, materia FROM asignaciones_estudiantes_materias")->fetchAll(PDO::FETCH_ASSOC);
        foreach($res_mat as $rm) {
            $materias_por_estudiante[$rm['estudiante_id']][] = $rm['materia'];
        }

        
        $lista_materias_disponibles = $pdo->query("SELECT DISTINCT nombre FROM materias WHERE nombre IS NOT NULL AND TRIM(nombre) != '' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_COLUMN);

        
        if (empty($lista_materias_disponibles)) {
            $lista_materias_disponibles = ['Matemática', 'Castellano', 'Biología', 'Física', 'Química', 'Inglés', 'Historia', 'Educación Física', 'Dibujo Técnico', 'Informática'];
        }

    } catch (PDOException $e) {
        $error_db = "Error al consultar personas: " . $e->getMessage();
    }
} else {
    $lista_materias_disponibles = ['Matemática', 'Castellano', 'Biología', 'Física', 'Química', 'Inglés', 'Historia', 'Educación Física', 'Dibujo Técnico', 'Informática'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personas y Secciones - <?= $nombre_institucion; ?></title>
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
                <div class="w-12 h-12 bg-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white font-bold shrink-0">
                    <i class="fa-solid fa-users-gear text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-xs font-bold leading-tight uppercase tracking-wider text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-indigo-400 font-medium">Pedro Garcia Leal</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Módulo Administrativo</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors text-sm font-medium">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>
            <a href="coordinacion.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors text-sm font-medium">
                <i class="fa-solid fa-chalkboard-user w-5 text-slate-400"></i> Coordinación
            </a>
            <a href="gestion_personas.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-md text-sm">
                <i class="fa-solid fa-id-card-clip w-5"></i> Gestión de Personas y Secciones
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 rounded-full bg-indigo-500 text-white font-black flex items-center justify-center">
                    <?= mb_substr($_SESSION['nombre'] ?? 'C', 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($_SESSION['nombre']); ?></p>
                    <p class="text-xs text-indigo-400 font-medium">@<?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
            </div>
        </div>
    </aside>

    
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-indigo-600 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Control y Asignación de Personas y Secciones</h2>
                    <p class="text-xs text-slate-500">Gestione la ubicación de sección, nivel académico y asignaturas por estudiante</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="abrirModalRegistro()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Registrar Persona
                </button>
                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200 hidden sm:inline-block">
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
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Buscar por Nombre, Apellido o Cédula</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej: Juan Pérez o V-29548..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Filtrar por Sección</label>
                            <select name="seccion" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-medium bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="todos">Todas las Secciones</option>
                                <?php foreach($secciones_db as $sec): ?>
                                    <option value="<?= htmlspecialchars($sec) ?>" <?= $filtro_seccion === $sec ? 'selected' : '' ?>>Sección <?= htmlspecialchars($sec) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow transition-all">
                                <i class="fa-solid fa-filter mr-1"></i> Filtrar
                            </button>
                            <a href="gestion_personas.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center transition-all" title="Limpiar filtros">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </form>
                </div>

                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                    <div class="p-6 border-b border-slate-200 bg-slate-50 flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Personas Registradas en el Sistema</h3>
                            <p class="text-xs text-slate-500">Total de registros encontrados: <span class="font-bold text-indigo-600"><?= $total_personas ?></span></p>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">Asigne secciones específicas (ej: 2A Matemática) y materias</span>
                    </div>

                    <?php if (empty($estudiantes)): ?>
                        <div class="p-12 text-center text-slate-400">
                            <i class="fa-solid fa-users-slash text-4xl mb-3 text-indigo-400"></i>
                            <p class="font-semibold text-slate-700 text-base">No se encontraron personas registradas con los filtros actuales.</p>
                            <p class="text-xs mt-1 text-slate-500">Pruebe limpiando la búsqueda o haciendo clic en "Registrar Persona".</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-bold">
                                    <tr>
                                        <th class="px-6 py-3">Persona / Estudiante</th>
                                        <th class="px-6 py-3">Cédula</th>
                                        <th class="px-6 py-3">Nivel Académico</th>
                                        <th class="px-6 py-3">Sección Asignada</th>
                                        <th class="px-6 py-3">Asignaturas Específicas</th>
                                        <th class="px-6 py-3 text-center">Acciones de Cambio</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php foreach ($estudiantes as $est): ?>
                                        <?php $mats_est = $materias_por_estudiante[$est['id']] ?? []; ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-6 py-4 font-semibold text-slate-800">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center shrink-0 border border-indigo-200 text-xs">
                                                        <?= mb_substr($est['nombre'], 0, 1) . mb_substr($est['apellido'], 0, 1) ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) ?></p>
                                                        <p class="text-xs text-slate-400"><?= htmlspecialchars($est['email'] ?? 'Sin correo electrónico') ?></p>
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
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    <i class="fa-solid fa-school"></i> Sección <?= htmlspecialchars($est['seccion']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-xs">
                                                <?php if (empty($mats_est)): ?>
                                                    <span class="text-slate-400 italic">Ninguna asignada</span>
                                                <?php else: ?>
                                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                                        <?php foreach($mats_est as $m): ?>
                                                            <span class="bg-amber-50 text-amber-800 px-2 py-0.5 rounded font-bold border border-amber-200 text-[11px]"><?= htmlspecialchars($m) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    
                                                    <button type="button" onclick="abrirModalSeccion(<?= $est['id'] ?>, '<?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['seccion'], ENT_QUOTES) ?>', '<?= htmlspecialchars($est['nivel_academico'] ?? '2do Año', ENT_QUOTES) ?>', '<?= htmlspecialchars($est['estado'] ?? 'Activo', ENT_QUOTES) ?>')" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-xl font-bold text-xs border border-indigo-300 transition-all flex items-center gap-1 shadow-sm" title="Cambiar Sección y Nivel">
                                                        <i class="fa-solid fa-pen-to-square"></i> Sección
                                                    </button>
                                                    
                                                    <button type="button" onclick="abrirModalMaterias(<?= $est['id'] ?>, '<?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($mats_est)) ?>)" class="bg-amber-500 hover:bg-amber-600 text-slate-950 px-3.5 py-1.5 rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 cursor-pointer" title="Asignar Asignaturas disponibles de Coordinación">
                                                        <i class="fa-solid fa-book-bookmark"></i> Asignar Asignaturas
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    
    <div id="modalSeccion" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden p-6 slide-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2 text-indigo-600 font-bold">
                    <i class="fa-solid fa-school text-lg"></i>
                    <h3 class="text-base text-slate-800">Cambiar Sección y Nivel</h3>
                </div>
                <button type="button" onclick="cerrarModalSeccion()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="estudiante_id" id="modal_sec_id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Persona / Estudiante:</label>
                    <input type="text" id="modal_sec_nombre" readonly class="w-full px-3 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm font-bold text-slate-700 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sección Destino (Ej: 2A, 1B, 2A Matemática) *</label>
                    <input type="text" name="nueva_seccion" id="modal_sec_codigo" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none" required placeholder="Ej: 2A o 2A Matematica">
                    <p class="text-[11px] text-slate-400 mt-1">Permite especificar tanto la letra de sección como la asignatura o modalidad deseada.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nivel Académico / Año</label>
                    <select name="nuevo_nivel" id="modal_sec_nivel" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                        <option value="1er Año">1er Año</option>
                        <option value="2do Año">2do Año</option>
                        <option value="3er Año">3er Año</option>
                        <option value="4to Año">4to Año</option>
                        <option value="5to Año">5to Año</option>
                        <option value="6to Año">6to Año</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Estado</label>
                    <select name="estado" id="modal_sec_estado" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                        <option value="Graduado">Graduado</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalSeccion()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="actualizar_seccion_nivel" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalMaterias" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-6 slide-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2 text-amber-600 font-bold">
                    <i class="fa-solid fa-book-bookmark text-lg"></i>
                    <h3 class="text-base text-slate-800">Asignar Asignaturas Disponibles (Coordinación)</h3>
                </div>
                <button type="button" onclick="cerrarModalMaterias()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="estudiante_id" id="modal_mat_estudiante_id" value="">
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Estudiante Seleccionado:</label>
                    <input type="text" id="modal_mat_estudiante_nombre" readonly class="w-full px-3 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-sm font-bold text-slate-700 cursor-not-allowed">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase">Seleccione las asignaturas que cursará:</label>
                        <span class="text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">Desde Coordinación</span>
                    </div>

                    <?php if (empty($lista_materias_disponibles)): ?>
                        <div class="p-4 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl text-xs font-medium">
                            <i class="fa-solid fa-circle-info mr-1"></i> No hay asignaturas creadas en el módulo de coordinación.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <?php foreach($lista_materias_disponibles as $mat): ?>
                                <label class="flex items-center gap-2.5 p-2 bg-white rounded-lg border border-slate-200 hover:border-amber-400 cursor-pointer transition-all text-xs font-semibold text-slate-700">
                                    <input type="checkbox" name="materias[]" value="<?= htmlspecialchars($mat) ?>" id="chk_mat_<?= md5($mat) ?>" class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                                    <span class="truncate"><?= htmlspecialchars($mat) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalMaterias()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="gestionar_materias_estudiante" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-bold shadow-md transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Asignaturas
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalRegistro" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-6 slide-up">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2 text-indigo-600 font-bold">
                    <i class="fa-solid fa-user-plus text-lg"></i>
                    <h3 class="text-base text-slate-800">Registrar Nueva Persona en el Sistema</h3>
                </div>
                <button type="button" onclick="cerrarModalRegistro()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cédula *</label>
                        <input type="text" name="cedula" required placeholder="Ej: V-30123456" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Correo Electrónico</label>
                        <input type="email" name="email" placeholder="correo@ejemplo.com" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombres *</label>
                        <input type="text" name="nombre" required placeholder="Ej: Carlos Eduardo" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Apellidos *</label>
                        <input type="text" name="apellido" required placeholder="Ej: Rodríguez Silva" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nivel Académico</label>
                        <select name="nivel_academico" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium">
                            <option value="1er Año">1er Año</option>
                            <option value="2do Año" selected>2do Año</option>
                            <option value="3er Año">3er Año</option>
                            <option value="4to Año">4to Año</option>
                            <option value="5to Año">5to Año</option>
                            <option value="6to Año">6to Año</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Sección Inicial *</label>
                        <input type="text" name="seccion" required value="2A" placeholder="Ej: 2A" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalRegistro()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="registrar_persona" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition-all">
                        <i class="fa-solid fa-user-check mr-1"></i> Registrar en el Sistema
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
        }

        function abrirModalSeccion(id, nombre, seccion, nivel, estado) {
            document.getElementById('modal_sec_id').value = id;
            document.getElementById('modal_sec_nombre').value = nombre;
            document.getElementById('modal_sec_codigo').value = seccion;
            document.getElementById('modal_sec_nivel').value = nivel;
            document.getElementById('modal_sec_estado').value = estado;
            const modal = document.getElementById('modalSeccion');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalSeccion() {
            const modal = document.getElementById('modalSeccion');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalMaterias(id, nombre, materiasAsignadas) {
            document.getElementById('modal_mat_estudiante_id').value = id;
            document.getElementById('modal_mat_estudiante_nombre').value = nombre;
            
            
            document.querySelectorAll('input[name="materias[]"]').forEach(chk => {
                chk.checked = false;
            });

            
            if (Array.isArray(materiasAsignadas)) {
                materiasAsignadas.forEach(mat => {
                    document.querySelectorAll('input[name="materias[]"]').forEach(chk => {
                        if (chk.value === mat) {
                            chk.checked = true;
                        }
                    });
                });
            }

            const modal = document.getElementById('modalMaterias');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalMaterias() {
            const modal = document.getElementById('modalMaterias');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalRegistro() {
            const modal = document.getElementById('modalRegistro');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalRegistro() {
            const modal = document.getElementById('modalRegistro');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
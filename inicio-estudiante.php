<?php
session_start();

$host = 'localhost';
$dbname = 'escuela_tecnica';
$username = 'root'; 
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Si falla, permitiremos que la interfaz cargue para modo de demostración/desarrollo
    error_log("Error de conexión en Laragon: " . $e->getMessage());
}

// Validación de sesión y rol de Estudiante
if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['rol'] !== 'Estudiante') {
    header('Location: index.php');
    exit;
}

// Identificar dinámicamente al estudiante logueado (Cédula o Nombre)
$usuario_activo = $_SESSION['usuario'] ?? $_SESSION['cedula'] ?? $_SESSION['username'] ?? '30123456'; 
$rol_actual = 'Estudiante';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";

$estudiante = null;
if (isset($pdo)) {
    // Buscar estudiante por su cédula o coincidencia de usuario
    $stmt = $pdo->prepare("SELECT id, cedula, nombre, apellido, seccion, nivel_academico FROM estudiantes WHERE cedula = ? OR nombre LIKE ? LIMIT 1");
    $stmt->execute([$usuario_activo, "%$usuario_activo%"]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si el estudiante no existe en la BD, creamos uno de prueba para visualización
if (!$estudiante) {
    $estudiante = [
        'id' => 1,
        'cedula' => '30123456',
        'nombre' => 'Estudiante',
        'apellido' => 'Demo',
        'seccion' => '2A',
        'nivel_academico' => '2do Año'
    ];
}

$seccion_asignada = trim($estudiante['seccion']);
$nivel_academico = trim($estudiante['nivel_academico'] ?? '');

// Obtener las actividades planificadas por los profesores para la sección del estudiante
$actividades_asignadas = [];
if (isset($pdo)) {
    try {
        // El profesor suele guardar la sección de forma combinada (Ej: "2A"), 
        // mientras el estudiante tiene la letra ("A") y el nivel ("2do Año") separados.
        $numero_grado = preg_replace('/[^0-9]/', '', $nivel_academico);
        $seccion_combinada = $numero_grado . $seccion_asignada; // Resultado: "2A"

        $stmt_ev = $pdo->prepare("
            SELECT * FROM evaluaciones 
            WHERE TRIM(seccion) = ? OR TRIM(seccion) = ? 
            ORDER BY fecha ASC
        ");
        $stmt_ev->execute([$seccion_asignada, $seccion_combinada]);
        $actividades_asignadas = $stmt_ev->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al consultar evaluaciones: " . $e->getMessage());
    }
}

// Consulta de asistencias registradas por el escáner QR (Cédula -> ID del estudiante)
$asistencias_db = [];
$total_dias = 0;
$presentes = 0;
$ausentes = 0;
$porcentaje_asistencia = 0;

if (isset($pdo) && $estudiante) {
    try {
        $stmt_asistencia = $pdo->prepare("SELECT fecha, hora, estado FROM asistencias WHERE id_estudiante = ? ORDER BY fecha DESC, hora DESC");
        $stmt_asistencia->execute([$estudiante['id']]);
        $asistencias_db = $stmt_asistencia->fetchAll(PDO::FETCH_ASSOC);
        
        $total_dias = count($asistencias_db);
        foreach ($asistencias_db as $asis) {
            if (trim(strtolower($asis['estado'])) === 'presente') {
                $presentes++;
            } else {
                $ausentes++;
            }
        }
        if ($total_dias > 0) {
            $porcentaje_asistencia = round(($presentes / $total_dias) * 100);
        }
    } catch(PDOException $e) {
        error_log("Error al consultar asistencias: " . $e->getMessage());
    }
} else {
    // Simulación del registro QR si la base de datos está desconectada/vacia temporalmente
    $asistencias_db = [
        ['fecha' => date('Y-m-d'), 'hora' => '07:15:32', 'estado' => 'Presente'],
        ['fecha' => date('Y-m-d', strtotime('-1 days')), 'hora' => '07:22:10', 'estado' => 'Presente'],
        ['fecha' => date('Y-m-d', strtotime('-2 days')), 'hora' => '00:00:00', 'estado' => 'Ausente'],
        ['fecha' => date('Y-m-d', strtotime('-3 days')), 'hora' => '07:05:44', 'estado' => 'Presente'],
    ];
    $total_dias = count($asistencias_db);
    $presentes = 3;
    $ausentes = 1;
    $porcentaje_asistencia = round(($presentes / $total_dias) * 100);
}

$vista = $_GET['vista'] ?? 'actividades';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Portal Estudiantil - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        .slide-up { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { 
            from { transform: translateY(20px); opacity: 0; } 
            to { transform: translateY(0); opacity: 1; } 
        }
        
        .seccion-vista { display: none; }
        .seccion-vista.activa { display: block; animation: fadeIn 0.4s ease; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <!-- Sidebar (Diseño actualizado con botón de Inicio) -->
    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30 text-white shrink-0">
                    <i class="fa-solid fa-graduation-cap text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-amber-400 font-medium">Pedro Garcia Leal</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <!-- Botón de Inicio / Index -->
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Navegación General</div>
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors mb-4">
                <i class="fa-solid fa-house w-5 text-amber-400"></i> Inicio (Menú Principal)
            </a>

            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Mi Área Académica</div>
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-colors">
                <i class="fa-solid fa-book-open w-5"></i> Mi Portal
            </a>
            
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Módulos Restringidos</div>
            <div class="flex items-center gap-3 px-4 py-3 text-slate-600 rounded-xl opacity-40 cursor-not-allowed">
                <i class="fa-solid fa-chalkboard-user w-5"></i> Panel Docente
            </div>
            <div class="flex items-center gap-3 px-4 py-3 text-slate-600 rounded-xl opacity-40 cursor-not-allowed">
                <i class="fa-solid fa-handshake-angle w-5"></i> Labor Social
            </div>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-amber-500 border border-slate-700">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></p>
                    <p class="text-xs text-slate-400">Estudiante Activo</p>
                </div>
            </div>
            <a href="cerrar-sesion.php" class="mt-4 flex items-center justify-center gap-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-950/30 w-full py-2.5 rounded-lg transition-colors border border-transparent hover:border-red-900/50 font-semibold">
                <i class="fa-solid fa-power-off"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Portal del Estudiante</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Consulta de asignaciones y asistencia QR</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> Hoy: <?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <!-- Tarjeta de Identidad del Estudiante -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-6 md:p-8 shadow-xl text-white relative overflow-hidden slide-up">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-amber-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 -translate-y-1/2 translate-x-1/2"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row gap-6 items-center md:items-start text-center md:text-left">
                        <div class="w-24 h-24 bg-white/10 p-2 rounded-2xl backdrop-blur-sm border border-white/20 shrink-0">
                            <div class="w-full h-full bg-slate-800 rounded-xl flex items-center justify-center text-4xl text-amber-400">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl md:text-3xl font-bold mb-1"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></h2>
                            <p class="text-slate-300 font-medium flex items-center justify-center md:justify-start gap-2 mb-4">
                                <i class="fa-regular fa-id-card text-amber-400"></i> Cédula: <?= htmlspecialchars($estudiante['cedula']); ?>
                            </p>
                            
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                                <div class="px-4 py-2 bg-white/10 rounded-xl border border-white/20 backdrop-blur-sm">
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-0.5">Sección Asignada</p>
                                    <p class="font-bold text-amber-400 text-lg leading-none">"<?= htmlspecialchars($seccion_asignada); ?>"</p>
                                </div>
                                <div class="px-4 py-2 bg-white/10 rounded-xl border border-white/20 backdrop-blur-sm">
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-0.5">Grado / Nivel</p>
                                    <p class="font-bold text-white text-lg leading-none"><?= htmlspecialchars($estudiante['nivel_academico']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botonera de Vistas (Tab Control) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2 slide-up" style="animation-delay: 0.1s;">
                    <button onclick="cambiarVista('actividades')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'actividades') ? 'bg-amber-500 text-slate-900 shadow-md shadow-amber-500/20' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-actividades">
                        <i class="fa-solid fa-list-check"></i> Actividades Asignadas
                    </button>
                    <button onclick="cambiarVista('asistencias')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'asistencias') ? 'bg-amber-500 text-slate-900 shadow-md shadow-amber-500/20' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-asistencias">
                        <i class="fa-solid fa-qrcode"></i> Mi Registro de Asistencias (QR)
                    </button>
                </div>

                <!-- VISTA 1: ACTIVIDADES ASIGNADAS -->
                <div id="actividades" class="seccion-vista <?= ($vista === 'actividades') ? 'activa' : ''; ?>">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-slate-800">Plan de Evaluaciones</h3>
                        <p class="text-sm text-slate-500 mt-1">Actividades programadas por los profesores para la sección <?= htmlspecialchars($seccion_asignada) ?></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 slide-up" style="animation-delay: 0.2s;">
                        <?php if (empty($actividades_asignadas)): ?>
                            <div class="col-span-full p-12 text-center bg-white border border-slate-200 rounded-3xl shadow-sm">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <i class="fa-solid fa-clipboard-check text-4xl text-slate-300"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 mb-1">Sin actividades pendientes</h3>
                                <p class="text-sm text-slate-500">No se han registrado evaluaciones para tu sección por el momento.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($actividades_asignadas as $act): ?>
                                <div class="bg-white border border-slate-200 rounded-2xl p-6 hover:border-amber-400 hover:shadow-lg transition-all relative flex flex-col group">
                                    <div class="absolute top-4 right-4 text-xs font-bold px-2 py-1 bg-amber-50 text-amber-700 rounded-md border border-amber-200">
                                        <?= htmlspecialchars($act['ponderacion']) ?>%
                                    </div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-500">
                                            <i class="fa-solid fa-tag text-xs"></i>
                                        </div>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= htmlspecialchars($act['tipo']) ?></span>
                                    </div>
                                    
                                    <h4 class="text-lg font-bold text-slate-800 mb-2 leading-tight group-hover:text-amber-600 transition-colors"><?= htmlspecialchars($act['titulo']) ?></h4>
                                    <div class="text-sm text-slate-500 mb-4 font-medium flex items-center gap-2">
                                        <i class="fa-regular fa-calendar-days text-amber-500"></i> Fecha: <?= date('d/m/Y', strtotime($act['fecha'])) ?>
                                    </div>
                                    
                                    <div class="mt-auto pt-4 border-t border-slate-100 text-xs font-medium text-slate-400 flex items-center justify-between">
                                        <span>Asignado por: Prof. <?= htmlspecialchars($act['creado_por'] ?? 'Docente') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- VISTA 2: ASISTENCIA QR -->
                <div id="asistencias" class="seccion-vista <?= ($vista === 'asistencias') ? 'activa' : ''; ?>">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-slate-800">Historial de Asistencia Escaneada</h3>
                        <p class="text-sm text-slate-500 mt-1">Logs de acceso capturados por el sistema QR de la institución con tu cédula.</p>
                    </div>

                    <!-- Indicadores Estadísticos en Fila -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 slide-up">
                        
                        <!-- Tarjeta Total Días -->
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 flex flex-col items-center justify-center text-center">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Días</span>
                            <span class="text-3xl font-bold text-slate-700"><?= $total_dias; ?></span>
                        </div>

                        <!-- Tarjeta Presentes -->
                        <div class="bg-emerald-50/50 rounded-2xl p-5 shadow-sm border border-emerald-200/60 flex flex-col items-center justify-center text-center">
                            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Presentes</span>
                            <span class="text-3xl font-bold text-emerald-600"><?= $presentes; ?></span>
                        </div>

                        <!-- Tarjeta Ausentes -->
                        <div class="bg-rose-50/50 rounded-2xl p-5 shadow-sm border border-rose-200/60 flex flex-col items-center justify-center text-center">
                            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider mb-1">Ausentes</span>
                            <span class="text-3xl font-bold text-rose-600"><?= $ausentes; ?></span>
                        </div>

                        <!-- Tarjeta % Asistencia -->
                        <div class="bg-amber-50/50 rounded-2xl p-5 shadow-sm border border-amber-200/60 flex flex-col items-center justify-center text-center">
                            <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider mb-1">% Asistencia</span>
                            <span class="text-3xl font-bold text-amber-700"><?= $porcentaje_asistencia; ?>%</span>
                        </div>
                    </div>

                    <!-- Bloque con borde discontinuo / Listado del Historial de Escaneo QR -->
                    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-slate-100 bg-slate-50/30">
                            <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Últimos Registros Detectados (Código QR)
                            </h4>
                        </div>
                        
                        <?php if (empty($asistencias_db)): ?>
                            <div class="p-12 text-center text-slate-400 border-2 border-dashed border-slate-200/60 rounded-2xl m-6">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <i class="fa-regular fa-calendar-xmark text-3xl text-slate-300"></i>
                                </div>
                                <h4 class="font-medium text-slate-600">No hay registros de asistencia en el sistema</h4>
                                <p class="text-xs text-slate-400 mt-1">Usa tu carnet o cédula en los tótems QR de la institución.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                        <tr>
                                            <th class="px-6 py-4">Fecha de Lectura</th>
                                            <th class="px-6 py-4">Hora de Registro</th>
                                            <th class="px-6 py-4">Método de Marcado</th>
                                            <th class="px-6 py-4 text-center">Estado de Ingreso</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php foreach ($asistencias_db as $asis): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-slate-800">
                                                <i class="fa-regular fa-calendar text-slate-400 mr-2"></i>
                                                <?= date('d/m/Y', strtotime($asis['fecha'])); ?>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600 font-medium">
                                                <i class="fa-regular fa-clock text-slate-400 mr-2"></i>
                                                <?= ($asis['hora'] !== '00:00:00') ? date('h:i A', strtotime($asis['hora'])) : '--:--'; ?>
                                            </td>
                                            <td class="px-6 py-4 text-slate-400 text-xs font-semibold uppercase">
                                                <span class="bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                                    <i class="fa-solid fa-qrcode mr-1"></i> QR Cédula Escáner
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if(trim(strtolower($asis['estado'])) === 'presente'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Presente
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded-lg">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div> Ausente
                                                    </span>
                                                <?php endif; ?>
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
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date("Y"); ?> <?= $nombre_institucion; ?>. Portal del Estudiante.</p>
            </footer>
        </div>
    </main>

    <script>
        function cambiarVista(vistaId) {
            // Actualizar URL sin recargar para mantener persistencia visual
            const url = new URL(window.location);
            url.searchParams.set('vista', vistaId);
            window.history.pushState({}, '', url);

            // Ocultar todas las vistas
            document.querySelectorAll('.seccion-vista').forEach(el => el.classList.remove('activa'));
            
            // Restablecer estilos de los botones de navegación
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50';
            });

            // Activar la vista seleccionada
            const seccionActiva = document.getElementById(vistaId);
            if(seccionActiva) seccionActiva.classList.add('activa');
            
            // Activar estilo visual del botón
            const botonActivo = document.getElementById('btn-' + vistaId);
            if(botonActivo) {
                botonActivo.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 bg-amber-500 text-slate-900 shadow-md shadow-amber-500/20';
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
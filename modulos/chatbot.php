<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$nombre_actual = $_SESSION['nombre'] ?? 'Usuario';
$modulo_actual = "Centro de Ayuda";


$preguntas_frecuentes = [
    [
        'id' => 'gen_1',
        'categoria' => 'General y Cuenta',
        'pregunta' => '¿Qué es este Sistema de Gestión Escolar?',
        'respuesta' => 'Es la plataforma administrativa y académica oficial de la ' . $nombre_institucion . '. Centraliza el control de notas, asistencia por QR, reportes de conducta, labor social, pasantías, reuniones y comunicación directa entre padres, profesores y directiva.'
    ],
    [
        'id' => 'user_1',
        'categoria' => 'Gestión de Usuarios',
        'pregunta' => '¿Cómo se administran los usuarios del sistema?',
        'respuesta' => 'En el módulo de "Usuarios" (exclusivo para Administradores y Coordinadores), puedes crear nuevas cuentas, registrar cédulas, asignar nombres de usuario, definir roles institucionales (Administrador, Secretaria, Coordinador, Profesor, Prácticas Profesionales, Labor Social, Estudiante, Representante) y alternar el estado entre Activo e Inactivo.'
    ],
    [
        'id' => 'user_2',
        'categoria' => 'Gestión de Usuarios',
        'pregunta' => '¿Qué roles existen en el sistema y qué permisos tienen?',
        'respuesta' => 'Los roles principales son: Administrador (acceso total), Coordinador Académico (control de notas y disciplina), Secretaria Dirección (inscripciones y planillas), Profesor (carga de notas, tareas y conducta), Prácticas Profesionales/Pasantías, Labor Social, Estudiante (consulta de su expediente) y Representante (seguimiento de sus hijos).'
    ],
    [
        'id' => 'prof_1',
        'categoria' => 'Profesores y Académico',
        'pregunta' => '¿Cómo gestiona el profesor sus clases y evaluaciones?',
        'respuesta' => 'En el módulo "Profesores", el cuerpo docente puede programar evaluaciones, subir material didáctico digital, calificar asignaciones y llevar un registro detallado del rendimiento de los estudiantes por sección.'
    ],
    [
        'id' => 'prof_2',
        'categoria' => 'Profesores y Académico',
        'pregunta' => '¿Cómo entregan los estudiantes sus tareas?',
        'respuesta' => 'Los estudiantes ingresan a su portal, visualizan las asignaciones pendientes en la sección de tareas del profesor correspondiente y adjuntan sus archivos o evidencias antes de la fecha límite establecida.'
    ],
    [
        'id' => 'est_1',
        'categoria' => 'Estudiantes y Representantes',
        'pregunta' => '¿Qué información ve el representante en su panel?',
        'respuesta' => 'El representante tiene acceso a un tablero integral donde puede consultar las calificaciones en tiempo real, historial de asistencia, reuniones convocadas, estatus de pasantías, labor social y los reportes de convivencia y conducta.'
    ],
    [
        'id' => 'est_2',
        'categoria' => 'Estudiantes y Representantes',
        'pregunta' => '¿Cómo funciona el módulo de Convivencia y Conducta?',
        'respuesta' => 'Registra incidencias de comportamiento divididas en 4 tipos: Felicitación (reconocimiento), Llamado de Atención (falta menor), Amonestación y Reporte Grave. Las últimas dos requieren que el representante firme digitalmente aceptando el estatus de "Leído y Conforme" con marca de tiempo y registro IP.'
    ],
    [
        'id' => 'asis_1',
        'categoria' => 'Asistencias',
        'pregunta' => '¿Cómo funciona el registro de asistencia con códigos QR?',
        'respuesta' => 'El sistema genera códigos QR dinámicos para cada clase o jornada. Los estudiantes escanean el código desde sus teléfonos o dispositivos institucionales para registrar su entrada de forma inmediata, segura y sin suplantación de identidad.'
    ],
    [
        'id' => 'asis_2',
        'categoria' => 'Asistencias',
        'pregunta' => '¿Qué sucede si un estudiante no tiene dispositivo o batería?',
        'respuesta' => 'El docente o el personal de guardia puede realizar el registro de asistencia manual directamente desde el panel de control administrativo para garantizar que el alumno no quede ausente injustificadamente.'
    ],
    [
        'id' => 'lab_1',
        'categoria' => 'Labor Social y Pasantías',
        'pregunta' => '¿Cómo se registran las horas de Labor Social?',
        'respuesta' => 'Los estudiantes o tutores encargados cargan las horas comunitarias realizadas especificando fecha, institución receptora, actividades ejecutadas y cantidad de horas. El coordinador de labor social debe aprobar y validar los registros en el sistema.'
    ],
    [
        'id' => 'pas_1',
        'categoria' => 'Labor Social y Pasantías',
        'pregunta' => '¿Cómo se maneja el módulo de Pasantías / Prácticas Profesionales?',
        'respuesta' => 'Permite registrar la empresa donde el estudiante cursará sus pasantías, datos del tutor empresarial, fechas de inicio y culminación, así como la evaluación final de desempeño requerida para titularse.'
    ],
    [
        'id' => 'reu_1',
        'categoria' => 'Reuniones y Comunicados',
        'pregunta' => '¿Cómo se convocan y confirman las reuniones con representantes?',
        'respuesta' => 'Los profesores o directivos programan reuniones indicando motivo, fecha y hora. Los representantes reciben la notificación en su panel y pueden hacer clic en "Confirmar Asistencia" para notificar su presencia anticipadamente.'
    ],
    [
        'id' => 'not_1',
        'categoria' => 'Reuniones y Comunicados',
        'pregunta' => '¿Para qué sirve el módulo de Noticias?',
        'respuesta' => 'Es el canal oficial de anuncios institucionales, comunicados de dirección, suspensión de actividades, cronogramas de exámenes y eventos especiales de la E.T. Pedro Garcia Leal.'
    ],
    [
        'id' => 'plan_1',
        'categoria' => 'Planillas y Trámites',
        'pregunta' => '¿Cómo se generan las planillas institucionales?',
        'respuesta' => 'En el módulo "Planillas", el personal administrativo puede generar constancias de estudio, certificados de notas, constancias de buena conducta y formatos oficiales listos para imprimir con los datos precargados del estudiante.'
    ]
];

function normalizar($texto) {
    $originales = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü','¿','?','¡','!','.',',',';',':'];
    $modificadas = ['a','e','i','o','u','a','e','i','o','u','n','n','u','u','','','','','','','',''];
    $texto = str_replace($originales, $modificadas, $texto);
    return mb_strtolower(trim($texto), 'UTF-8');
}

$termino_busqueda = '';
$resultados = [];
$categoria_seleccionada = $_GET['cat'] ?? 'todas';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['pregunta'])) {
    $termino_busqueda = trim($_POST['pregunta'] ?? $_GET['pregunta'] ?? '');
    
    if (!empty($termino_busqueda)) {
        $norm_busqueda = normalizar($termino_busqueda);
        $palabras_busqueda = array_filter(explode(' ', $norm_busqueda));

        foreach ($preguntas_frecuentes as $item) {
            if ($categoria_seleccionada !== 'todas' && $item['categoria'] !== $categoria_seleccionada) {
                continue;
            }

            $norm_pregunta = normalizar($item['pregunta']);
            $norm_respuesta = normalizar($item['respuesta']);
            $norm_categoria = normalizar($item['categoria']);
            $texto_completo = $norm_pregunta . ' ' . $norm_respuesta . ' ' . $norm_categoria;

            $coincide_frase = (mb_strpos($texto_completo, $norm_busqueda) !== false);

            $coincidencias_palabras = 0;
            if (!empty($palabras_busqueda)) {
                foreach ($palabras_busqueda as $palabra) {
                    if (mb_strpos($texto_completo, $palabra) !== false) {
                        $coincidencias_palabras++;
                    }
                }
            }

            $umbral_necesario = count($palabras_busqueda) > 1 ? ceil(count($palabras_busqueda) * 0.6) : 1;

            if ($coincide_frase || $coincidencias_palabras >= $umbral_necesario) {
                $resultados[] = $item;
            }
        }
    }
}

$por_categoria = [];
foreach ($preguntas_frecuentes as $item) {
    $cat = $item['categoria'];
    if (!isset($por_categoria[$cat])) {
        $por_categoria[$cat] = [];
    }
    $por_categoria[$cat][] = $item;
}


$escudo_svg = '
<svg viewBox="0 0 200 240" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M100 8L182 34V110C182 166 142 212 100 232C58 212 18 166 18 110V34L100 8Z" fill="#facc15" stroke="#b45309" stroke-width="6"/>
    <path d="M26 40L100 18L174 40V105C174 155 138 196 100 214C62 196 26 155 26 105V40Z" fill="#ffffff"/>
    <path d="M26 95L174 125V175C174 175 140 210 100 220C60 210 26 175 26 175V95Z" fill="#7f1d1d"/>
    <text x="100" y="50" font-size="11" font-family="Arial, sans-serif" font-weight="900" fill="#0f172a" text-anchor="middle" letter-spacing="0.5">INFORMÁTICA, COMERCIO</text>
    <text x="100" y="65" font-size="11" font-family="Arial, sans-serif" font-weight="900" fill="#0f172a" text-anchor="middle" letter-spacing="0.5">Y SERVICIOS ADMIN.</text>
    <rect x="45" y="78" width="45" height="40" rx="4" fill="#7f1d1d" stroke="#facc15" stroke-width="2.5"/>
    <rect x="53" y="86" width="29" height="24" rx="2" fill="none" stroke="#ffffff" stroke-width="2"/>
    <rect x="61" y="94" width="13" height="8" fill="#ffffff"/>
    <g transform="rotate(-5 100 148)">
        <rect x="25" y="133" width="150" height="28" fill="#facc15" rx="4" stroke="#ca8a04" stroke-width="1.5"/>
        <text x="100" y="152" font-size="11.5" font-family="Arial, sans-serif" font-weight="900" fill="#7f1d1d" text-anchor="middle">E.T. "PEDRO GARCÍA LEAL"</text>
    </g>
    <g transform="translate(82, 172)">
        <path d="M0 10C10 5 20 10 30 5V25C20 30 10 25 0 30V10Z" fill="#ffffff"/>
        <path d="M30 5C40 10 50 5 60 10V30C50 25 40 30 30 25V5Z" fill="#e2e8f0"/>
        <text x="30" y="14" font-size="5.5" font-weight="900" fill="#7f1d1d" text-anchor="middle">FUNDADO EL</text>
        <text x="30" y="22" font-size="5.5" font-weight="900" fill="#7f1d1d" text-anchor="middle">01-10-1975</text>
    </g>
    <ellipse cx="100" cy="218" rx="40" ry="11" fill="#7f1d1d"/>
    <text x="100" y="222" font-size="8.5" font-family="Arial, sans-serif" font-weight="900" fill="#facc15" text-anchor="middle" letter-spacing="0.5">VALERA EDO TRUJILLO</text>
</svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modulo_actual; ?> - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        .slide-up { animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }

        .brand-logo-sidebar {
            width: 52px;
            height: 62px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 4px 10px rgba(245, 158, 11, 0.4));
            transition: transform 0.3s ease;
        }
        .brand-logo-sidebar:hover { transform: scale(1.08); }
        .brand-logo-sidebar svg { width: 100%; height: 100%; display: block; }

        .banner-logo-center {
            width: 85px;
            height: 100px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.5));
        }
        .banner-logo-center svg { width: 100%; height: 100%; display: block; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <div id="app-container" class="flex w-full h-full">
        <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
            <div class="p-5 flex items-center justify-between lg:justify-start gap-3.5 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="brand-logo-sidebar">
                        <?= $escudo_svg; ?>
                    </div>
                    <div>
                        <h1 class="text-xs font-extrabold uppercase text-slate-200 tracking-wider">Escuela Técnica</h1>
                        <p class="text-xs text-amber-400 font-bold uppercase tracking-wider">Pedro Garcia Leal</p>
                    </div>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Panel General</div>
                <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors"><i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio</a>
                
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Asistente y Guía</div>
                <a href="chatbot.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md transition-colors"><i class="fa-solid fa-headset w-5"></i> Centro de Ayuda</a>
                
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Módulos del Sistema</div>
                <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors"><i class="fa-solid fa-newspaper w-5 text-slate-400"></i> Noticias</a>
            </nav>
        </aside>

        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 text-xl p-2 rounded-lg hover:bg-slate-100"><i class="fa-solid fa-bars"></i></button>
                    <div>
                        
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Centro de Ayuda</h2>
                        <p class="text-xs text-slate-500">Asistente oficial y guía completa de funcionalidades del sistema</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-600 hidden sm:inline"><?= htmlspecialchars($nombre_actual); ?></span>
                    <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold shadow-sm"><?= strtoupper(substr($nombre_actual, 0, 1)); ?></div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
                <div class="max-w-5xl mx-auto space-y-6">

                    
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-700/50 relative overflow-hidden">
                        <div class="space-y-2 text-center sm:text-left z-10">
                            <span class="inline-block bg-amber-500/20 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-500/30">Soporte Técnico & Guía</span>
                            <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Centro de Ayuda Institucional</h2>
                            <p class="text-slate-300 text-sm max-w-xl">Consulta preguntas frecuentes, guías paso a paso de cada módulo o busca términos clave para utilizar la plataforma correctamente.</p>
                        </div>
                        <div class="banner-logo-center z-10">
                            <?= $escudo_svg; ?>
                        </div>
                    </div>

                    
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 lg:p-8 slide-up">
                        <h3 class="text-lg font-bold text-slate-800 mb-2">¿Qué funcionalidad deseas consultar o entender?</h3>
                        <p class="text-sm text-slate-500 mb-6">Busca por módulo, nombre de tarea, proceso académico o palabra clave.</p>
                        
                        <form method="POST" class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input type="text" name="pregunta" placeholder="Ej. Labor social, usuarios, código QR, conducta, notas..." 
                                       value="<?= htmlspecialchars($termino_busqueda); ?>"
                                       class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500 font-medium">
                            </div>
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-3 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-search"></i> Buscar en el Sistema
                            </button>
                        </form>
                    </div>

                    
                    <?php if (!empty($termino_busqueda)): ?>
                        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 rounded-xl flex items-center justify-between slide-up">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-lightbulb text-amber-500 text-xl"></i>
                                <span class="text-sm font-medium">Se encontraron <strong><?= count($resultados); ?></strong> resultado(s) para "<strong><?= htmlspecialchars($termino_busqueda); ?></strong>"</span>
                            </div>
                            <a href="chatbot.php" class="text-xs font-bold text-amber-700 hover:underline">Limpiar búsqueda</a>
                        </div>

                        <?php if (!empty($resultados)): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 slide-up">
                                <?php foreach ($resultados as $item): ?>
                                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                                    <div>
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-100 text-amber-800 mb-3">
                                            <?= htmlspecialchars($item['categoria']); ?>
                                        </span>
                                        <h4 class="font-bold text-slate-800 text-base mb-2 flex items-start gap-2">
                                            <i class="fa-solid fa-circle-question text-amber-500 mt-1"></i> 
                                            <?= htmlspecialchars($item['pregunta']); ?>
                                        </h4>
                                        <p class="text-slate-600 text-sm leading-relaxed">
                                            <?= nl2br(htmlspecialchars($item['respuesta'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center slide-up">
                                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <h4 class="text-lg font-bold text-slate-800 mb-2">No se encontraron resultados</h4>
                                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">No hay coincidencias directas para tu búsqueda. Explora las categorías completas a continuación o contacta con administración.</p>
                                <a href="chatbot.php" class="inline-block bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">Ver todas las preguntas</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if (empty($termino_busqueda)): ?>
                        <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-lg slide-up">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-star"></i></div>
                                <div>
                                    <h3 class="font-bold text-base">Accesos Rápidos y Consultas Frecuentes</h3>
                                    <p class="text-xs text-slate-400">Haz clic en cualquiera de las consultas más comunes para ver su respuesta inmediata.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <?php 
                                $populares = ['user_1', 'asis_1', 'est_2', 'lab_1', 'reu_1', 'prof_1'];
                                foreach ($populares as $id):
                                    foreach ($preguntas_frecuentes as $item):
                                        if ($item['id'] === $id):
                                ?>
                                <button onclick="buscarRapido('<?= htmlspecialchars($item['pregunta'], ENT_QUOTES); ?>')" class="bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 text-left p-3 rounded-xl text-xs font-medium transition-colors text-slate-200 flex items-center justify-between group">
                                    <span class="line-clamp-1"><?= htmlspecialchars($item['pregunta']); ?></span>
                                    <i class="fa-solid fa-chevron-right text-slate-500 group-hover:text-amber-400 transition-colors shrink-0 ml-2"></i>
                                </button>
                                <?php 
                                        endif;
                                    endforeach;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    
                    <div class="space-y-8 slide-up">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                            <h3 class="text-lg font-bold text-slate-800">📚 Guía Completa por Módulos del Sistema</h3>
                            <span class="text-xs text-slate-500 font-medium">Documentación Oficial</span>
                        </div>

                        <?php foreach ($por_categoria as $categoria => $preguntas): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                                <h4 class="font-bold text-slate-800 text-base flex items-center gap-2">
                                    <i class="fa-solid fa-folder-open text-amber-500"></i> <?= htmlspecialchars($categoria); ?>
                                </h4>
                                <span class="text-xs font-semibold bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg"><?= count($preguntas); ?> temas</span>
                            </div>
                            
                            <div class="divide-y divide-slate-100">
                                <?php foreach ($preguntas as $item): ?>
                                <div class="p-6 hover:bg-slate-50/60 transition-colors">
                                    <h5 class="font-bold text-slate-800 text-sm mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-circle-question text-amber-500 text-xs"></i> 
                                        <?= htmlspecialchars($item['pregunta']); ?>
                                    </h5>
                                    <p class="text-slate-600 text-sm leading-relaxed pl-5 border-l-2 border-amber-400/40 my-2">
                                        <?= nl2br(htmlspecialchars($item['respuesta'])); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    
                    <div class="bg-amber-500 text-white rounded-2xl p-8 shadow-md flex flex-col md:flex-row items-center justify-between gap-6 slide-up">
                        <div class="space-y-2 text-center md:text-left">
                            <div class="inline-flex items-center gap-2 bg-white/20 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">
                                <i class="fa-solid fa-headset"></i> Soporte Institucional
                            </div>
                            <h3 class="text-xl font-bold">¿Necesitas asistencia técnica personalizada?</h3>
                            <p class="text-amber-100 text-sm max-w-xl">Si experimentas problemas con tu cuenta, pérdida de datos o requieres soporte avanzado, comunícate directamente con la administración de la escuela.</p>
                        </div>
                        <div class="bg-white text-slate-900 px-6 py-4 rounded-xl font-bold shadow-lg text-center shrink-0">
                            <div class="text-xs text-slate-500 uppercase">Horario de Atención</div>
                            <div class="text-sm font-extrabold text-amber-600">Lunes a Viernes</div>
                            <div class="text-xs text-slate-600">8:00 AM - 5:00 PM</div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }

        function buscarRapido(pregunta) {
            const input = document.querySelector('input[name="pregunta"]');
            input.value = pregunta;
            input.form.submit();
        }
    </script>
</body>
</html>
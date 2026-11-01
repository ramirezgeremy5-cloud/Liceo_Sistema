<?php
session_start();
if (!isset($_SESSION['usuario_logueado'])) { header('Location: ../login.php'); exit; }

$rol_actual = $_SESSION['rol'] ?? 'Administrador';
$nombre_actual = $_SESSION['nombre'] ?? 'Administrador';
$vista = $_GET['vista'] ?? 'empresas';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$año_actual = date("Y");

$tutores_dummy = [
    'Prof. Carlos Pérez (Informática)', 
    'Profa. María González (Administración)', 
    'Prof. José Rodríguez (Electrónica)', 
    'Profa. Ana Martínez (Contabilidad)'
];

$host = 'localhost';
$dbname = 'escuela_tecnica';
$username_db = 'root'; 
$password_db = '';     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt_est = $pdo->query("SELECT id, cedula, nombre, apellido, seccion FROM estudiantes WHERE estado = 'Activo' ORDER BY nombre ASC");
    $estudiantes_db = $stmt_est->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error de conexión a BD. Detalle: " . $e->getMessage());
}

if (isset($_GET['exportar']) && $_GET['exportar'] == '1') {
    $stmt = $pdo->query("SELECT p.estudiante, p.proyecto, e.nombre as empresa, p.tutor_academico, p.periodo, IF(p.informe_recibido=1, 'Entregado', 'Pendiente') as informe, p.estado 
                         FROM pasantias_proyectos p 
                         LEFT JOIN empresas e ON p.empresa_id = e.id 
                         WHERE p.estado = 'Finalizada'");
    $historial_csv = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_pasantias_completas.csv');
    $salida = fopen('php://output', 'w');
    fputcsv($salida, ['Estudiante', 'Proyecto', 'Empresa', 'Tutor Académico', 'Periodo', 'Informe Final', 'Estado']);
    foreach ($historial_csv as $fila) { fputcsv($salida, $fila); }
    fclose($salida);
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_empresa'])) {
    $sql = "INSERT INTO empresas (nombre, icono, descripcion, direccion, contacto, areas) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        htmlspecialchars($_POST['nombre_empresa']),
        htmlspecialchars($_POST['icono_empresa'] ?: 'fa-solid fa-building'),
        htmlspecialchars($_POST['desc_empresa']),
        htmlspecialchars($_POST['dir_empresa']),
        htmlspecialchars($_POST['contacto_empresa']),
        htmlspecialchars($_POST['areas_empresa'])
    ]);
    header("Location: pasantias.php?vista=empresas");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_pasantia'])) {
    $codigo_verificacion = 'PGL-PAS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    
    $sql = "INSERT INTO pasantias_proyectos (estudiante, proyecto, empresa_id, fecha, duracion, estado, tutor_academico, tutor_empresarial, codigo_verificacion) 
            VALUES (?, ?, ?, ?, ?, 'Activa', ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        htmlspecialchars($_POST['estudiante_nombre']),
        htmlspecialchars($_POST['proyecto_nombre']),
        $_POST['empresa_id'],
        $_POST['fecha_inicio'],
        htmlspecialchars($_POST['duracion']),
        htmlspecialchars($_POST['tutor_academico']),
        htmlspecialchars($_POST['tutor_empresarial']),
        $codigo_verificacion
    ]);
    header("Location: pasantias.php?vista=activas");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_pasantia'])) {
    $id_pasantia = (int)$_POST['id_pasantia'];
    $periodo_actual = date('Y') . '-' . (date('n') > 6 ? 'II' : 'I'); 
    
    $sql = "UPDATE pasantias_proyectos SET estado = 'Finalizada', periodo = ?, informe_recibido = 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$periodo_actual, $id_pasantia]);
    
    header("Location: pasantias.php?vista=anteriores");
    exit;
}

$stmt = $pdo->query("SELECT * FROM empresas ORDER BY nombre ASC");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT p.*, e.nombre as empresa_nombre, e.direccion as empresa_direccion FROM pasantias_proyectos p LEFT JOIN empresas e ON p.empresa_id = e.id WHERE p.estado = 'Activa'");
$activas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT p.*, e.nombre as empresa_nombre FROM pasantias_proyectos p LEFT JOIN empresas e ON p.empresa_id = e.id WHERE p.estado = 'Finalizada' ORDER BY p.id DESC");
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($empresas as &$emp) {
    $emp['asignados'] = [];
    foreach ($activas as $activa) {
        if ($activa['empresa_id'] == $emp['id']) {
            $emp['asignados'][] = $activa['estudiante'] . ' (Proyecto: ' . $activa['proyecto'] . ')';
        }
    }
}
unset($emp);


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
    <title>Prácticas Profesionales - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        .seccion-vista { display: none; }
        .seccion-vista.activa { display: block; animation: fadeIn 0.4s ease; }
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
        
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <div id="zona-impresion" class="hidden print-only print:absolute print:inset-0 print:bg-white print:z-50 p-10 max-w-4xl mx-auto text-justify text-slate-800 font-serif">
        <div class="text-center mb-10 border-b-2 border-slate-800 pb-6">
            <h3 class="font-bold uppercase tracking-wide text-sm">República Bolivariana de Venezuela</h3>
            <h3 class="font-bold uppercase tracking-wide text-sm">Ministerio del Poder Popular para la Educación</h3>
            <h2 class="font-black uppercase tracking-wider mt-2 text-lg"><?= $nombre_institucion ?></h2>
            <p class="text-xs mt-1 italic">Dirección de Prácticas Profesionales y Vinculación Empresarial</p>
        </div>
        
        <div class="text-right mb-12">
            <p id="print_fecha" class="font-medium text-sm">Fecha: ---</p>
        </div>

        <h1 class="text-xl font-bold text-center uppercase underline mb-10 tracking-widest">Carta de Asignación de Pasantías</h1>

        <div class="space-y-6 leading-relaxed text-[15px]">
            <p>A quien corresponda,</p>
            <p>Por medio de la presente, la Coordinación de Pasantías de la <strong><?= $nombre_institucion ?></strong>, hace constar que el (la) estudiante <strong id="print_estudiante" class="uppercase">---</strong>, ha sido oficialmente asignado(a) para cumplir con el programa obligatorio de Prácticas Profesionales en la institución/empresa: <strong id="print_empresa" class="uppercase">---</strong>.</p>
            <p>Durante este periodo, el estudiante desarrollará el proyecto titulado: <strong id="print_proyecto" class="italic">---</strong>. El proceso formativo tendrá una duración estipulada de <strong id="print_duracion">---</strong>, iniciando formalmente el <strong id="print_fecha_inicio">---</strong>.</p>
            <p>Este proceso contará con la supervisión institucional del/la <strong id="print_tutor_acad">---</strong> en calidad de <strong>Tutor Académico</strong>, y el acompañamiento en las instalaciones de la empresa por parte del/la <strong id="print_tutor_emp">---</strong> en calidad de <strong>Tutor Empresarial</strong>.</p>
            <p>Agradecemos de antemano el apoyo brindado a nuestra institución y a la formación técnico-profesional de nuestros jóvenes estudiantes.</p>
        </div>

        <div class="mt-32 grid grid-cols-2 gap-20 text-center">
            <div>
                <div class="border-t border-slate-500 pt-2 font-bold text-sm">Firma del Tutor Académico</div>
                <div class="text-xs text-slate-500 mt-1" id="print_firma_acad">---</div>
            </div>
            <div>
                <div class="border-t border-slate-500 pt-2 font-bold text-sm">Sello y Firma (Coordinación)</div>
                <div class="text-xs text-slate-500 mt-1"><?= $nombre_institucion ?></div>
            </div>
        </div>

        <div class="mt-20 pt-4 border-t border-dashed border-slate-300 text-[10px] text-slate-400 font-sans flex justify-between">
            <p>Documento generado automáticamente por el Sistema Académico.</p>
            <p>Código de Verificación: <strong id="print_codigo" class="text-slate-600">---</strong></p>
        </div>
    </div>

    <div id="app-container" class="flex w-full h-full no-print">
        <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
            <div class="p-5 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="brand-logo-sidebar">
                        <?= $escudo_svg; ?>
                    </div>
                    <div>
                        <h1 class="text-xs font-extrabold leading-tight uppercase tracking-wider text-slate-200">Escuela Técnica</h1>
                        <p class="text-xs text-amber-400 font-bold uppercase tracking-wider">Pedro Garcia Leal</p>
                    </div>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Panel General</div>
                <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                    <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
                </a>
               

                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Secciones y Canales</div>
                <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                    <i class="fa-solid fa-bullhorn w-5 text-slate-400"></i> Muro de Noticias
                </a>
                <a href="pasantias.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all">
                    <i class="fa-solid fa-briefcase w-5"></i> Pasantías
                </a>
            </nav>
        </aside>

        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="text-xl lg:text-2xl font-extrabold text-slate-800 leading-tight">Módulo de Pasantías</h2>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-700"><?= htmlspecialchars($nombre_actual); ?></p>
                        <p class="text-[10px] text-amber-600 font-bold uppercase"><?= htmlspecialchars($rol_actual); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold shadow-sm">
                        <?= strtoupper(substr($nombre_actual, 0, 1)); ?>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
                <div class="max-w-6xl mx-auto space-y-6">
                    
                    
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-700/50 relative overflow-hidden">
                        <div class="space-y-2 text-center sm:text-left z-10">
                            <span class="inline-block bg-amber-500/20 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-500/30">Prácticas Profesionales</span>
                            <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Coordinación de Pasantías</h2>
                            <p class="text-slate-300 text-sm max-w-xl">Gestión de convenios empresariales, seguimiento de alumnos pasantes e impresión de cartas oficiales de vinculación.</p>
                        </div>
                        <div class="banner-logo-center z-10">
                            <?= $escudo_svg; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2">
                        <button onclick="cambiarVista('empresas')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'empresas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-empresas">
                            <i class="fa-solid fa-building"></i> 1. Directorio Empresas
                        </button>
                        <button onclick="cambiarVista('activas')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'activas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-activas">
                            <i class="fa-solid fa-file-signature"></i> 2. Control y Desarrollo
                        </button>
                        <button onclick="cambiarVista('anteriores')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'anteriores') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-anteriores">
                            <i class="fa-solid fa-check-double"></i> 3. Cierre / Historial
                        </button>
                    </div>

                    
                    <div id="empresas" class="seccion-vista <?= ($vista === 'empresas') ? 'activa' : ''; ?>">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-6 slide-up">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800">Empresas e Instituciones Vacantes</h3>
                                <p class="text-sm text-slate-500 mt-1">Registra aquí las empresas disponibles para recibir estudiantes.</p>
                            </div>
                            <button onclick="abrirModal('modalCrearEmpresa')" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-plus"></i> Nueva Empresa
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 slide-up">
                            <?php foreach ($empresas as $emp): ?>
                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-amber-400 hover:shadow-lg transition-all group flex flex-col relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-12 h-12 bg-slate-50 text-slate-600 group-hover:text-white group-hover:bg-amber-500 rounded-xl flex items-center justify-center text-xl transition-all duration-300 border border-slate-100">
                                            <i class="<?= htmlspecialchars($emp['icono']) ?>"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-md font-bold text-slate-800 leading-tight"><?= htmlspecialchars($emp['nombre']); ?></h3>
                                            <span class="text-xs text-slate-500 font-medium"><i class="fa-solid fa-users text-amber-500"></i> <?= count($emp['asignados']); ?> Pasantes</span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500 line-clamp-2 flex-1 mb-4"><?= htmlspecialchars($emp['descripcion']); ?></p>
                                    <div class="text-xs text-slate-600 space-y-2 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <div class="flex items-start gap-2"><i class="fa-solid fa-location-dot mt-0.5 text-slate-400"></i> <span><?= htmlspecialchars($emp['direccion']); ?></span></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    
                    <div id="activas" class="seccion-vista <?= ($vista === 'activas') ? 'activa' : ''; ?>">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Control y Desarrollo de Pasantías</h3>
                                    <p class="text-sm text-slate-500">Genera cartas de validación y administra asignaciones.</p>
                                </div>
                                <button onclick="abrirModal('modalAsignarPasantia')" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                    <i class="fa-solid fa-user-plus"></i> Iniciar Proceso (Asignar)
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <?php if (empty($activas)): ?>
                                    <div class="text-center py-12 text-slate-400">No hay estudiantes en desarrollo actualmente.</div>
                                <?php else: ?>
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                            <tr>
                                                <th class="px-6 py-4">Estudiante / Proyecto</th>
                                                <th class="px-6 py-4">Empresa / Tutores</th>
                                                <th class="px-6 py-4">Tiempos</th>
                                                <th class="px-6 py-4 text-center">Acciones del Proceso</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <?php foreach ($activas as $activa): ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($activa['estudiante']) ?></p>
                                                    <p class="text-xs text-slate-500 mt-1 italic"><?= htmlspecialchars($activa['proyecto']) ?></p>
                                                </td>
                                                <td class="px-6 py-4 text-xs">
                                                    <p class="font-bold text-slate-700 mb-1"><i class="fa-solid fa-building text-amber-500 mr-1"></i> <?= htmlspecialchars($activa['empresa_nombre']) ?></p>
                                                    <p class="text-slate-500">Acad: <?= htmlspecialchars($activa['tutor_academico'] ?? 'N/A') ?></p>
                                                    <p class="text-slate-500">Emp: <?= htmlspecialchars($activa['tutor_empresarial'] ?? 'N/A') ?></p>
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 text-xs">
                                                    <p>Inicio: <?= date('d/m/Y', strtotime($activa['fecha'])) ?></p>
                                                    <p class="font-bold text-slate-700 mt-0.5"><?= htmlspecialchars($activa['duracion']) ?></p>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick='imprimirCarta(<?= json_encode($activa) ?>)' class="bg-slate-100 text-slate-700 hover:bg-slate-900 hover:text-white border border-slate-200 px-3 py-2 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5" title="Generar Carta Validación">
                                                            <i class="fa-solid fa-file-pdf text-red-500"></i> Generar Carta
                                                        </button>
                                                        <button onclick='abrirModalCierre(<?= $activa['id'] ?>, <?= json_encode($activa['estudiante']) ?>)' class="bg-emerald-50 text-emerald-700 hover:bg-emerald-500 hover:text-white border border-emerald-200 px-3 py-2 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5" title="Cerrar Proceso">
                                                            <i class="fa-solid fa-check-double"></i> Recibir Informe
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div id="anteriores" class="seccion-vista <?= ($vista === 'anteriores') ? 'activa' : ''; ?>">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Procesos Finalizados</h3>
                                    <p class="text-sm text-slate-500">Historial de pasantías con informes entregados y aprobados.</p>
                                </div>
                                <a href="pasantias.php?exportar=1" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                    <i class="fa-solid fa-file-csv"></i> Exportar Historial
                                </a>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <?php if (empty($historial)): ?>
                                    <div class="text-center py-12 text-slate-400">No hay procesos finalizados registrados.</div>
                                <?php else: ?>
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                            <tr>
                                                <th class="px-6 py-4">Estudiante / Empresa</th>
                                                <th class="px-6 py-4">Tutoría</th>
                                                <th class="px-6 py-4">Periodo</th>
                                                <th class="px-6 py-4 text-center">Informe y Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <?php foreach ($historial as $hist): ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($hist['estudiante']) ?></p>
                                                    <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-building text-amber-500 mr-1"></i> <?= htmlspecialchars($hist['empresa_nombre']) ?></p>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-600">
                                                    A: <?= htmlspecialchars($hist['tutor_academico'] ?? 'N/A') ?><br>
                                                    E: <?= htmlspecialchars($hist['tutor_empresarial'] ?? 'N/A') ?>
                                                </td>
                                                <td class="px-6 py-4 font-medium text-slate-700"><?= htmlspecialchars($hist['periodo'] ?? 'N/A') ?></td>
                                                <td class="px-6 py-4 text-center">
                                                    <?php if($hist['informe_recibido']): ?>
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-200 mb-1 block w-max mx-auto">
                                                            <i class="fa-solid fa-folder-check"></i> Informe Entregado
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold rounded-lg border bg-slate-100 text-slate-700 border-slate-200 block w-max mx-auto">
                                                        Proceso Cerrado
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    
    <div id="modalCrearEmpresa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 class="text-xl font-bold text-slate-800">Registrar Empresa</h2>
                <button onclick="cerrarModal('modalCrearEmpresa')" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4 bg-white max-h-[70vh] overflow-y-auto">
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Nombre</label><input type="text" name="nombre_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Categoría/Icono</label>
                    <select name="icono_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50">
                        <option value="fa-solid fa-building">Edificio General</option>
                        <option value="fa-solid fa-network-wired">Tecnología / Redes</option>
                        <option value="fa-solid fa-desktop">Informática</option>
                        <option value="fa-solid fa-industry">Industria</option>
                        <option value="fa-solid fa-store">Comercio / Tienda</option>
                    </select>
                </div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Áreas</label><input type="text" name="areas_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Contacto</label><input type="text" name="contacto_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Dirección</label><input type="text" name="dir_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                <div><label class="block text-sm font-semibold text-slate-700 mb-1">Descripción</label><textarea name="desc_empresa" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 resize-none" required></textarea></div>
                <button type="submit" name="nueva_empresa" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-xl shadow-lg mt-2"><i class="fa-solid fa-check"></i> Registrar Empresa</button>
            </form>
        </div>
    </div>

    <div id="modalAsignarPasantia" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Inicio de Proceso: Asignar Pasante</h2>
                    <p class="text-xs text-slate-500">Completa los datos para estructurar el desarrollo.</p>
                </div>
                <button onclick="cerrarModal('modalAsignarPasantia')" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4 bg-white max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estudiante Participante</label>
                        <select name="estudiante_nombre" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <option value="">Seleccione estudiante...</option>
                            <?php foreach($estudiantes_db as $est): $nombre = trim($est['nombre'] . ' ' . $est['apellido']); ?>
                                <option value="<?= htmlspecialchars($nombre) ?>"><?= htmlspecialchars($nombre . ' (C.I: ' . $est['cedula'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Empresa Destino</label>
                        <select name="empresa_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <option value="">Seleccione empresa vacante...</option>
                            <?php foreach($empresas as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Proyecto a Desarrollar</label>
                    <input type="text" name="proyecto_nombre" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" placeholder="Ej: Migración de infraestructura de red..." required>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"><i class="fa-solid fa-chalkboard-user mr-1"></i> Estructura de Tutoría</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tutor Académico (Plantel)</label>
                            <select name="tutor_academico" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" required>
                                <option value="">Asignar docente...</option>
                                <?php foreach($tutores_dummy as $tutor): ?>
                                    <option value="<?= $tutor ?>"><?= $tutor ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tutor Empresarial (Empresa)</label>
                            <input type="text" name="tutor_empresarial" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" placeholder="Nombre del encargado en la empresa" required>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Fecha de Inicio</label><input type="date" name="fecha_inicio" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Duración (Cronograma)</label>
                        <select name="duracion" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <option value="6 Semanas (Min. requerido)">6 Semanas (Min. requerido)</option>
                            <option value="8 Semanas">8 Semanas</option>
                            <option value="12 Semanas (Extendido)">12 Semanas (Extendido)</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="asignar_pasantia" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3.5 rounded-xl transition-all shadow-lg mt-4 flex justify-center items-center gap-2">
                    <i class="fa-solid fa-play"></i> Registrar Inicio de Proceso
                </button>
            </form>
        </div>
    </div>

    <div id="modalCierre" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden border border-white/20 text-center p-8">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 border-4 border-emerald-50">
                <i class="fa-solid fa-file-circle-check"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Cierre de Pasantía</h2>
            <p class="text-sm text-slate-600 mb-6">¿Confirmas la recepción física/digital del <strong>Informe Final de Pasantías</strong> para el estudiante <span id="cierre_nombre_est" class="font-bold"></span>?</p>
            
            <form method="POST">
                <input type="hidden" name="id_pasantia" id="cierre_id_pasantia">
                
                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-700 text-left mb-6 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5"></i>
                    <p>Al confirmar, el proceso pasará a "Finalizado", se registrará la entrega del informe y se cerrará el ciclo activo del estudiante.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModal('modalCierre')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancelar</button>
                    <button type="submit" name="finalizar_pasantia" class="flex-1 px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-semibold transition-colors shadow-lg shadow-emerald-500/30">Sí, Recibí Informe</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function cambiarVista(vistaId) {
            const url = new URL(window.location);
            url.searchParams.set('vista', vistaId);
            window.history.pushState({}, '', url);

            document.querySelectorAll('.seccion-vista').forEach(el => el.classList.remove('activa'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50';
            });

            const seccionActiva = document.getElementById(vistaId);
            if(seccionActiva) seccionActiva.classList.add('activa');
            const botonActivo = document.getElementById('btn-' + vistaId);
            if(botonActivo) botonActivo.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 bg-slate-900 text-white shadow-md';
        }

        function abrirModal(id) {
            const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex');
        }
        function cerrarModal(id) {
            const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex');
        }

        function abrirModalCierre(id, nombre) {
            document.getElementById('cierre_id_pasantia').value = id;
            document.getElementById('cierre_nombre_est').innerText = nombre;
            abrirModal('modalCierre');
        }

        function imprimirCarta(datos) {
            const hoy = new Date().toLocaleDateString('es-VE', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('print_fecha').innerText = `Fecha de emisión: ${hoy}`;
            document.getElementById('print_estudiante').innerText = datos.estudiante;
            document.getElementById('print_empresa').innerText = datos.empresa_nombre;
            document.getElementById('print_proyecto').innerText = datos.proyecto;
            document.getElementById('print_duracion').innerText = datos.duracion;
            
            const f_ini = new Date(datos.fecha).toLocaleDateString('es-VE', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('print_fecha_inicio').innerText = f_ini;
            
            document.getElementById('print_tutor_acad').innerText = datos.tutor_academico || 'No especificado';
            document.getElementById('print_tutor_emp').innerText = datos.tutor_empresarial || 'No especificado';
            document.getElementById('print_firma_acad').innerText = datos.tutor_academico || 'Firma Tutor Institucional';
            document.getElementById('print_codigo').innerText = datos.codigo_verificacion || 'PGL-MANUAL-0000';

            window.print();
        }

        function toggleSidebar() {
            const sb = document.getElementById('sidebar'); const ov = document.getElementById('sidebarOverlay');
            sb.classList.toggle('hidden'); sb.classList.toggle('flex'); ov.classList.toggle('hidden');
        }
    </script>
</body>
</html>
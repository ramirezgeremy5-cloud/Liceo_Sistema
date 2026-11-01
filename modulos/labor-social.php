<?php
session_start();
if (!isset($_SESSION['usuario_logueado'])) { header('Location: ../login.php'); exit; }

$host = 'localhost';
$dbname = 'escuela_tecnica';
$username_db = 'root'; 
$password_db = '';     

$tutores_dummy = [
    'Prof. Carlos Pérez (Sociedad)', 
    'Profa. María González (Vinculación)', 
    'Prof. José Rodríguez (Deportes)', 
    'Profa. Ana Martínez (Ciencias)'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT id, cedula, nombre, apellido, seccion FROM estudiantes WHERE estado = 'Activo' ORDER BY nombre ASC");
    $estudiantes_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a BD. Detalle: " . $e->getMessage());
}

$rol_actual = $_SESSION['rol'] ?? 'Administrador';
$nombre_actual = $_SESSION['nombre'] ?? ''; 
$username_actual = $_SESSION['username'] ?? '';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Labor Social";
$vista = $_GET['vista'] ?? 'registros';

$puede_editar = (
    $rol_actual === 'Administrador' || 
    $rol_actual === 'administrador' || 
    $rol_actual === 'Labor Social' || 
    stripos($nombre_actual, 'Neriz') !== false
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion_org']) && $_POST['accion_org'] === 'crear') {
        $sql = "INSERT INTO organizaciones_labor (nombre, direccion, descripcion, icono) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([htmlspecialchars($_POST['nombre']), htmlspecialchars($_POST['direccion']), htmlspecialchars($_POST['desc']), $_POST['icono']]);
        header("Location: labor-social.php?vista=empresas"); exit;
    }

    if (isset($_POST['accion_org']) && $_POST['accion_org'] === 'editar' && $puede_editar) {
        $sql = "UPDATE organizaciones_labor SET nombre = ?, direccion = ?, descripcion = ?, icono = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            htmlspecialchars($_POST['nombre']),
            htmlspecialchars($_POST['direccion']),
            htmlspecialchars($_POST['desc']),
            $_POST['icono'],
            $_POST['id_org']
        ]);
        header("Location: labor-social.php?vista=empresas"); exit;
    }

    if (isset($_POST['accion_org']) && $_POST['accion_org'] === 'eliminar' && $puede_editar) {
        $sql = "DELETE FROM organizaciones_labor WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_org']]);
        header("Location: labor-social.php?vista=empresas"); exit;
    }

    if (isset($_POST['nuevo_registro'])) {
        $codigo_verificacion = 'PGL-LAB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        $sql = "INSERT INTO registros_labor (estudiante, id_organizacion, horas, fecha_inicio, fecha_fin, estado, tutor_academico, tutor_comunitario, codigo_verificacion) 
                VALUES (?, ?, ?, ?, ?, 'En progreso', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            htmlspecialchars($_POST['estudiante']),
            $_POST['org_id'],
            $_POST['horas'],
            $_POST['fecha_inicio'],
            $_POST['fecha_fin'],
            htmlspecialchars($_POST['tutor_academico']),
            htmlspecialchars($_POST['tutor_comunitario']),
            $codigo_verificacion
        ]);
        header("Location: labor-social.php?vista=registros"); exit;
    }

    if (isset($_POST['finalizar_labor'])) {
        $sql = "UPDATE registros_labor SET estado = 'Finalizado', informe_recibido = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_labor']]);
        header("Location: labor-social.php?vista=registros"); exit;
    }

    if (isset($_POST['editar_labor']) && $puede_editar) {
        $sql = "UPDATE registros_labor SET estudiante = ?, id_organizacion = ?, horas = ?, fecha_inicio = ?, fecha_fin = ?, tutor_academico = ?, tutor_comunitario = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            htmlspecialchars($_POST['estudiante']),
            $_POST['org_id'],
            $_POST['horas'],
            $_POST['fecha_inicio'],
            $_POST['fecha_fin'],
            htmlspecialchars($_POST['tutor_academico']),
            htmlspecialchars($_POST['tutor_comunitario']),
            $_POST['id_labor']
        ]);
        header("Location: labor-social.php?vista=registros"); exit;
    }

    if (isset($_POST['eliminar_labor']) && $puede_editar) {
        $sql = "DELETE FROM registros_labor WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_labor']]);
        header("Location: labor-social.php?vista=registros"); exit;
    }
}

$stmt_org = $pdo->query("SELECT * FROM organizaciones_labor ORDER BY nombre ASC");
$organizaciones = $stmt_org->fetchAll(PDO::FETCH_ASSOC);

$stmt_reg = $pdo->query("SELECT r.*, o.nombre as nombre_org, o.direccion as dir_org FROM registros_labor r LEFT JOIN organizaciones_labor o ON r.id_organizacion = o.id ORDER BY r.estado DESC, r.fecha_registro DESC");
$registros = $stmt_reg->fetchAll(PDO::FETCH_ASSOC);


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
            <p class="text-xs mt-1 italic">Coordinación de Labor Social y Desarrollo Comunitario</p>
        </div>
        <div class="text-right mb-12">
            <p id="print_fecha" class="font-medium text-sm">Fecha: ---</p>
        </div>
        <h1 class="text-xl font-bold text-center uppercase underline mb-10 tracking-widest">Carta de Vinculación a Labor Social</h1>
        <div class="space-y-6 leading-relaxed text-[15px]">
            <p>Se hace constar por medio de la presente, que el (la) estudiante <strong id="print_estudiante" class="uppercase">---</strong>, perteneciente a nuestra casa de estudios, ha sido aprobado para realizar su Trabajo Comunitario (Labor Social) en la institución / comunidad: <strong id="print_comunidad" class="uppercase">---</strong> ubicada en <span id="print_dir">---</span>.</p>
            <p>El estudiante cumplirá con un total de <strong id="print_horas">---</strong> horas reglamentarias, comprendidas en el lapso desde el <strong id="print_inicio">---</strong> hasta el <strong id="print_fin">---</strong>, como requisito indispensable para optar al título de Educación Media Técnica.</p>
            <p>Durante este proceso, el control institucional será ejercido por el/la docente <strong id="print_tut_acad">---</strong>, mientras que el control comunitario in situ estará a cargo del ciudadano(a) <strong id="print_tut_com">---</strong>.</p>
            <p>Exhortamos al estudiante a demostrar los más altos valores éticos, cívicos y morales propios de nuestra institución durante el desarrollo de sus actividades comunitarias.</p>
        </div>
        <div class="mt-32 grid grid-cols-2 gap-20 text-center">
            <div>
                <div class="border-t border-slate-500 pt-2 font-bold text-sm">Tutor Académico</div>
                <div class="text-xs text-slate-500 mt-1" id="print_firma_acad">---</div>
            </div>
            <div>
                <div class="border-t border-slate-500 pt-2 font-bold text-sm">Sello Institucional</div>
                <div class="text-xs text-slate-500 mt-1"><?= $nombre_institucion ?></div>
            </div>
        </div>
        <div class="mt-20 pt-4 border-t border-dashed border-slate-300 text-[10px] text-slate-400 font-sans flex justify-between">
            <p>Documento oficial emitido por el Sistema de Control.</p>
            <p>Código de Verificación: <strong id="print_codigo" class="text-slate-600">---</strong></p>
        </div>
    </div>

    
    <div id="app-container" class="flex w-full h-full no-print">
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
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Módulos</div>
                <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md transition-colors"><i class="fa-solid fa-handshake-angle w-5"></i> Labor Social</a>
                <a href="pasantias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all"><i class="fa-solid fa-briefcase w-5 text-slate-400"></i> Pasantías</a>
            </nav>
        </aside>

        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 text-xl p-2 rounded-lg hover:bg-slate-100"><i class="fa-solid fa-bars"></i></button>
                    <div><h2 class="text-xl lg:text-2xl font-extrabold text-slate-800">Módulo de Labor Social</h2></div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-700"><?= htmlspecialchars($nombre_actual); ?></p>
                        <p class="text-[10px] text-amber-600 font-bold uppercase"><?= htmlspecialchars($rol_actual); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold shadow-sm">
                        <?= strtoupper(substr($nombre_actual ?: 'U', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
                <div class="max-w-6xl mx-auto space-y-6">
                    
                   
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-700/50 relative overflow-hidden">
                        <div class="space-y-2 text-center sm:text-left z-10">
                            <span class="inline-block bg-amber-500/20 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-500/30">Desarrollo Comunitario</span>
                            <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Coordinación de Labor Social</h2>
                            <p class="text-slate-300 text-sm max-w-xl">Vinculación de estudiantes con comunidades, registro de proyectos de servicio comunitario y validación de horas cumplidas.</p>
                        </div>
                        <div class="banner-logo-center z-10">
                            <?= $escudo_svg; ?>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 flex flex-col sm:flex-row gap-2">
                        <button onclick="cambiarVista('empresas')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'empresas') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-empresas">
                            <i class="fa-solid fa-building-ngo"></i> Instituciones (Vacantes)
                        </button>
                        <button onclick="cambiarVista('registros')" class="tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 <?= ($vista === 'registros') ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' ?>" id="btn-registros">
                            <i class="fa-solid fa-list-check"></i> Desarrollo y Cierre
                        </button>
                    </div>

                    <div id="registros" class="seccion-vista <?= ($vista === 'registros') ? 'activa' : ''; ?>">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                            <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Control de Labor Social</h3>
                                    <p class="text-sm text-slate-500">Asigna alumnos, genera cartas y aprueba informes finales.</p>
                                </div>
                                <button onclick="abrirModal('modalRegistro')" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                    <i class="fa-solid fa-link"></i> Iniciar Vinculación
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                        <tr>
                                            <th class="px-6 py-4">Estudiante / Horas</th>
                                            <th class="px-6 py-4">Comunidad / Tutores</th>
                                            <th class="px-6 py-4">Lapso</th>
                                            <th class="px-6 py-4 text-center">Acciones y Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php foreach ($registros as $reg): ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors <?= $reg['estado'] == 'Finalizado' ? 'bg-slate-50/50 opacity-90' : '' ?>">
                                            <td class="px-6 py-4">
                                                <p class="font-bold text-slate-800"><?= htmlspecialchars($reg['estudiante']) ?></p>
                                                <span class="inline-flex mt-1 items-center gap-1 bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-xs font-medium"><i class="fa-regular fa-clock"></i> <?= $reg['horas'] ?>h asignadas</span>
                                            </td>
                                            <td class="px-6 py-4 text-xs">
                                                <p class="font-bold text-slate-700 mb-1"><i class="fa-solid fa-people-roof text-amber-500 mr-1"></i> <?= htmlspecialchars($reg['nombre_org'] ?? 'Desconocida'); ?></p>
                                                <p class="text-slate-500">A: <?= htmlspecialchars($reg['tutor_academico'] ?? 'N/A'); ?></p>
                                                <p class="text-slate-500">C: <?= htmlspecialchars($reg['tutor_comunitario'] ?? 'N/A'); ?></p>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500 text-xs">
                                                Del <?= date('d/m/y', strtotime($reg['fecha_inicio'])) ?> <br> al <?= date('d/m/y', strtotime($reg['fecha_fin'])) ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if($reg['estado'] == 'Finalizado'): ?>
                                                    <div class="flex flex-col items-center gap-2">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <i class="fa-solid fa-folder-check"></i> Informe y Cierre Aprobado
                                                        </span>
                                                        
                                                        <?php if($puede_editar): ?>
                                                        <div class="flex items-center justify-center gap-2 mt-1">
                                                            <button onclick="abrirModalEditarLabor(<?= htmlspecialchars(json_encode($reg), ENT_QUOTES, 'UTF-8') ?>)" class="text-slate-500 hover:text-blue-600 border border-slate-200 hover:border-blue-200 bg-white hover:bg-blue-50 px-2.5 py-1.5 rounded-lg shadow-sm text-xs font-semibold transition-all flex items-center gap-1.5">
                                                                <i class="fa-solid fa-pen"></i> Editar
                                                            </button>
                                                            <button onclick="abrirModalEliminarLabor(<?= $reg['id'] ?>, '<?= htmlspecialchars($reg['estudiante'], ENT_QUOTES, 'UTF-8') ?>')" class="text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 bg-white hover:bg-red-50 px-2.5 py-1.5 rounded-lg shadow-sm text-xs font-semibold transition-all flex items-center gap-1.5">
                                                                <i class="fa-solid fa-trash"></i> Eliminar
                                                            </button>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick='imprimirCartaLabor(<?= json_encode($reg) ?>)' class="bg-slate-100 text-slate-700 hover:bg-slate-900 hover:text-white border border-slate-200 px-3 py-2 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5">
                                                            <i class="fa-solid fa-print"></i> Carta
                                                        </button>
                                                        <button onclick='abrirModalCierreLabor(<?= $reg['id'] ?>, <?= json_encode($reg['estudiante']) ?>)' class="bg-amber-50 text-amber-700 hover:bg-amber-500 hover:text-white border border-amber-200 px-3 py-2 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5">
                                                            <i class="fa-solid fa-check"></i> Recibir Inf.
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div id="empresas" class="seccion-vista <?= ($vista === 'empresas') ? 'activa' : ''; ?>">
                        <div class="flex justify-between items-end gap-4 mb-6 slide-up">
                            <div><h3 class="text-xl font-bold text-slate-800">Instituciones y Comunidades</h3></div>
                            <button onclick="abrirModal('modalOrg')" class="bg-slate-900 text-white font-medium py-2.5 px-5 rounded-xl"><i class="fa-solid fa-plus"></i> Añadir</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 slide-up">
                            <?php foreach ($organizaciones as $org): ?>
                                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-amber-400 group text-center flex flex-col relative">
                                    <div class="w-16 h-16 bg-slate-50 text-slate-600 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto border border-slate-100"><i class="<?= htmlspecialchars($org['icono']) ?>"></i></div>
                                    <h3 class="text-lg font-bold text-slate-800 mb-2"><?= htmlspecialchars($org['nombre']); ?></h3>
                                    <p class="text-xs text-slate-500 line-clamp-2 mb-4"><?= htmlspecialchars($org['descripcion']); ?></p>
                                    
                                    <?php if($puede_editar): ?>
                                    <div class="mt-auto pt-4 border-t border-slate-100 flex justify-center gap-2">
                                        <button onclick="abrirModalEditarOrg(<?= htmlspecialchars(json_encode($org), ENT_QUOTES, 'UTF-8') ?>)" class="text-slate-500 hover:text-blue-600 border border-slate-200 hover:border-blue-200 bg-white hover:bg-blue-50 px-3 py-1.5 rounded-lg shadow-sm text-xs font-semibold transition-all flex items-center gap-1.5">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                        <button onclick="abrirModalEliminarOrg(<?= $org['id'] ?>, '<?= htmlspecialchars($org['nombre'], ENT_QUOTES, 'UTF-8') ?>')" class="text-slate-500 hover:text-red-600 border border-slate-200 hover:border-red-200 bg-white hover:bg-red-50 px-3 py-1.5 rounded-lg shadow-sm text-xs font-semibold transition-all flex items-center gap-1.5">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    
    <div id="modalRegistro" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl slide-up overflow-hidden glass-modal">
            <div class="flex justify-between items-center p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Iniciar Vínculo: Labor Social</h2>
                    <p class="text-xs text-slate-500">Asigna comunidad y tutores responsables.</p>
                </div>
                <button onclick="cerrarModal('modalRegistro')" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estudiante</label>
                        <select name="estudiante" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <option value="">Seleccione estudiante...</option>
                            <?php foreach($estudiantes_db as $est): $nom = trim($est['nombre'] . ' ' . $est['apellido']); ?>
                                <option value="<?= htmlspecialchars($nom) ?>"><?= htmlspecialchars($nom . ' (C.I: ' . $est['cedula'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Comunidad / Institución</label>
                        <select name="org_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <option value="">Seleccione destino...</option>
                            <?php foreach($organizaciones as $org): ?>
                                <option value="<?= $org['id']; ?>"><?= htmlspecialchars($org['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="p-4 bg-amber-50/50 rounded-xl border border-amber-100 space-y-4">
                    <h4 class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-2"><i class="fa-solid fa-users mr-1"></i> Tutores del Proceso</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tutor Académico</label>
                            <select name="tutor_academico" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" required>
                                <option value="">Seleccione docente...</option>
                                <?php foreach($tutores_dummy as $tutor): ?>
                                    <option value="<?= $tutor ?>"><?= $tutor ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Responsable en Comunidad</label>
                            <input type="text" name="tutor_comunitario" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" placeholder="Ej: Lcdo. Pedro Pérez" required>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Horas Totales</label><input type="number" name="horas" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" value="120" required></div>
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Fecha Inicio</label><input type="date" name="fecha_inicio" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Fin Estimado</label><input type="date" name="fecha_fin" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                </div>
                <button type="submit" name="nuevo_registro" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3.5 rounded-xl mt-4"><i class="fa-solid fa-play"></i> Iniciar y Generar Control</button>
            </form>
        </div>
    </div>

    <div id="modalCierreLabor" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden text-center p-8">
            <div class="w-20 h-20 bg-amber-100 text-amber-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fa-solid fa-book-open-reader"></i></div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Aprobación de Labor Social</h2>
            <p class="text-sm text-slate-600 mb-6">¿Confirmas haber recibido y evaluado positivamente el <strong>Informe de Labor Social</strong> del estudiante <span id="cierre_lab_est" class="font-bold"></span>?</p>
            
            <form method="POST">
                <input type="hidden" name="id_labor" id="cierre_id_labor">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModal('modalCierreLabor')" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">Cancelar</button>
                    <button type="submit" name="finalizar_labor" class="flex-1 px-4 py-3 bg-emerald-500 text-white rounded-xl font-semibold hover:bg-emerald-600 transition-colors">Aprobar y Cerrar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalOrg" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"><form method="POST"><input type="hidden" name="accion_org" value="crear"><div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Nueva Institución</h2><button type="button" onclick="cerrarModal('modalOrg')" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-lg"></i></button></div><div class="space-y-4"><div><label class="block text-sm font-semibold mb-1">Nombre</label><input type="text" name="nombre" class="w-full px-4 py-2 border rounded-xl" required></div><div><label class="block text-sm font-semibold mb-1">Icono</label><select name="icono" class="w-full px-4 py-2 border rounded-xl"><option value="fa-solid fa-building-ngo">General</option><option value="fa-solid fa-people-roof">Comunidad</option></select></div><div><label class="block text-sm font-semibold mb-1">Dirección</label><input type="text" name="direccion" class="w-full px-4 py-2 border rounded-xl" required></div><div><label class="block text-sm font-semibold mb-1">Descripción</label><textarea name="desc" class="w-full px-4 py-2 border rounded-xl"></textarea></div><button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-semibold hover:bg-slate-800 transition-colors">Guardar</button></div></form></div>
    </div>

    <?php if($puede_editar): ?>
    <div id="modalEditarOrg" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <form method="POST">
                <input type="hidden" name="accion_org" value="editar">
                <input type="hidden" name="id_org" id="edit_org_id_input">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Editar Institución</h2>
                    <button type="button" onclick="cerrarModal('modalEditarOrg')" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="space-y-4">
                    <div><label class="block text-sm font-semibold mb-1">Nombre</label><input type="text" name="nombre" id="edit_org_nombre" class="w-full px-4 py-2 border rounded-xl" required></div>
                    <div><label class="block text-sm font-semibold mb-1">Icono</label><select name="icono" id="edit_org_icono" class="w-full px-4 py-2 border rounded-xl"><option value="fa-solid fa-building-ngo">General</option><option value="fa-solid fa-people-roof">Comunidad</option></select></div>
                    <div><label class="block text-sm font-semibold mb-1">Dirección</label><input type="text" name="direccion" id="edit_org_direccion" class="w-full px-4 py-2 border rounded-xl" required></div>
                    <div><label class="block text-sm font-semibold mb-1">Descripción</label><textarea name="desc" id="edit_org_desc" class="w-full px-4 py-2 border rounded-xl"></textarea></div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition-colors">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEliminarOrg" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden text-center p-8 border-t-4 border-red-500">
            <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Eliminar Institución</h2>
            <p class="text-sm text-slate-600 mb-6">¿Estás seguro que deseas eliminar permanentemente la institución <strong id="delete_org_nombre" class="text-slate-800"></strong>?</p>
            
            <form method="POST">
                <input type="hidden" name="accion_org" value="eliminar">
                <input type="hidden" name="id_org" id="delete_org_id_input">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModal('modalEliminarOrg')" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors"><i class="fa-solid fa-trash mr-1"></i> Sí, Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditarLabor" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl slide-up overflow-hidden glass-modal">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-blue-50/50">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Editar Registro Finalizado</h2>
                    <p class="text-xs text-slate-500">Modifica los detalles del proceso de labor social.</p>
                </div>
                <button onclick="cerrarModal('modalEditarLabor')" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <input type="hidden" name="id_labor" id="edit_id_labor">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estudiante</label>
                        <select name="estudiante" id="edit_estudiante" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <?php foreach($estudiantes_db as $est): $nom = trim($est['nombre'] . ' ' . $est['apellido']); ?>
                                <option value="<?= htmlspecialchars($nom) ?>"><?= htmlspecialchars($nom . ' (C.I: ' . $est['cedula'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Comunidad / Institución</label>
                        <select name="org_id" id="edit_org_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required>
                            <?php foreach($organizaciones as $org): ?>
                                <option value="<?= $org['id']; ?>"><?= htmlspecialchars($org['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="p-4 bg-amber-50/50 rounded-xl border border-amber-100 space-y-4">
                    <h4 class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-2"><i class="fa-solid fa-users mr-1"></i> Tutores del Proceso</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tutor Académico</label>
                            <select name="tutor_academico" id="edit_tutor_academico" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" required>
                                <?php foreach($tutores_dummy as $tutor): ?>
                                    <option value="<?= $tutor ?>"><?= $tutor ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Responsable en Comunidad</label>
                            <input type="text" name="tutor_comunitario" id="edit_tutor_comunitario" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white" required>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Horas Totales</label><input type="number" name="horas" id="edit_horas" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Fecha Inicio</label><input type="date" name="fecha_inicio" id="edit_fecha_inicio" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                    <div><label class="block text-sm font-semibold text-slate-700 mb-1">Fecha Fin</label><input type="date" name="fecha_fin" id="edit_fecha_fin" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50" required></div>
                </div>
                
                <button type="submit" name="editar_labor" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-xl mt-4 transition-colors"><i class="fa-solid fa-save mr-2"></i> Guardar Cambios</button>
            </form>
        </div>
    </div>

    <div id="modalEliminarLabor" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4 no-print">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden text-center p-8 border-t-4 border-red-500">
            <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Eliminar Registro</h2>
            <p class="text-sm text-slate-600 mb-6">¿Estás seguro que deseas eliminar permanentemente el registro finalizado del estudiante <strong id="delete_nombre_estudiante" class="text-slate-800"></strong>?</p>
            
            <form method="POST">
                <input type="hidden" name="id_labor" id="delete_id_labor">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModal('modalEliminarLabor')" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">Cancelar</button>
                    <button type="submit" name="eliminar_labor" class="flex-1 px-4 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors"><i class="fa-solid fa-trash mr-1"></i> Sí, Eliminar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function cambiarVista(vistaId) {
            const url = new URL(window.location); url.searchParams.set('vista', vistaId); window.history.pushState({}, '', url);
            document.querySelectorAll('.seccion-vista').forEach(el => el.classList.remove('activa'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 text-slate-500 hover:bg-slate-50');
            document.getElementById(vistaId).classList.add('activa');
            document.getElementById('btn-' + vistaId).className = 'tab-btn flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all flex justify-center items-center gap-2 bg-slate-900 text-white shadow-md';
        }

        function abrirModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
        function cerrarModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }

        function abrirModalCierreLabor(id, nombre) {
            document.getElementById('cierre_id_labor').value = id;
            document.getElementById('cierre_lab_est').innerText = nombre;
            abrirModal('modalCierreLabor');
        }

        function abrirModalEditarLabor(data) {
            document.getElementById('edit_id_labor').value = data.id;
            document.getElementById('edit_estudiante').value = data.estudiante;
            document.getElementById('edit_org_id').value = data.id_organizacion;
            document.getElementById('edit_tutor_academico').value = data.tutor_academico;
            document.getElementById('edit_tutor_comunitario').value = data.tutor_comunitario;
            document.getElementById('edit_horas').value = data.horas;
            document.getElementById('edit_fecha_inicio').value = data.fecha_inicio;
            document.getElementById('edit_fecha_fin').value = data.fecha_fin;
            abrirModal('modalEditarLabor');
        }

        function abrirModalEliminarLabor(id, nombre) {
            document.getElementById('delete_id_labor').value = id;
            document.getElementById('delete_nombre_estudiante').innerText = nombre;
            abrirModal('modalEliminarLabor');
        }

        function abrirModalEditarOrg(org) {
            document.getElementById('edit_org_id_input').value = org.id;
            document.getElementById('edit_org_nombre').value = org.nombre;
            document.getElementById('edit_org_direccion').value = org.direccion;
            document.getElementById('edit_org_desc').value = org.descripcion;
            document.getElementById('edit_org_icono').value = org.icono;
            abrirModal('modalEditarOrg');
        }

        function abrirModalEliminarOrg(id, nombre) {
            document.getElementById('delete_org_id_input').value = id;
            document.getElementById('delete_org_nombre').innerText = nombre;
            abrirModal('modalEliminarOrg');
        }

        function imprimirCartaLabor(datos) {
            const hoy = new Date().toLocaleDateString('es-VE', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('print_fecha').innerText = `Fecha: ${hoy}`;
            document.getElementById('print_estudiante').innerText = datos.estudiante;
            document.getElementById('print_comunidad').innerText = datos.nombre_org || 'Sin Organización';
            document.getElementById('print_dir').innerText = datos.dir_org || 'su respectiva ubicación';
            document.getElementById('print_horas').innerText = datos.horas;
            document.getElementById('print_inicio').innerText = new Date(datos.fecha_inicio).toLocaleDateString('es-VE');
            document.getElementById('print_fin').innerText = new Date(datos.fecha_fin).toLocaleDateString('es-VE');
            
            document.getElementById('print_tut_acad').innerText = datos.tutor_academico || 'No asignado';
            document.getElementById('print_tut_com').innerText = datos.tutor_comunitario || 'No asignado';
            document.getElementById('print_firma_acad').innerText = datos.tutor_academico || 'Firma Institucional';
            document.getElementById('print_codigo').innerText = datos.codigo_verificacion || 'PGL-LAB-MANUAL';

            window.print();
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar').classList.toggle('flex');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>
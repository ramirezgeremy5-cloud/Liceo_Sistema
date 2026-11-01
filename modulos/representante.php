<?php
/**
 * Módulo de Representante - Consulta de Representados y Calificaciones por Asignatura/Lapso
 */
session_start();

$host = 'localhost';
$dbname = 'escuela_tecnica';
$user = 'root';
$pass = '';

$error_db = "";
$pdo = null;

$representados = [];
$estudiante_seleccionado = null;
$notas_por_materia = [];
$cedula_rep = $_SESSION['cedula'] ?? $_SESSION['username'] ?? 'V-12345678';
$nombre_usuario = $_SESSION['nombre'] ?? 'Representante';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error_db = "Error de conexión: " . $e->getMessage();
}

if ($pdo) {
    try {
        
        $stmt_u = $pdo->prepare("SELECT cedula FROM usuarios WHERE username = ? OR id = ? LIMIT 1");
        $stmt_u->execute([$cedula_rep, is_numeric($cedula_rep) ? (int)$cedula_rep : 0]);
        $real_ci = $stmt_u->fetchColumn();
        if ($real_ci) { $cedula_rep = $real_ci; }

        
        $stmt_rep = $pdo->prepare("
            SELECT e.id, e.cedula, e.nombre, e.apellido, e.nivel_academico, e.seccion, r.parentesco
            FROM estudiantes e
            INNER JOIN representantes_estudiantes r ON e.id = r.estudiante_id
            WHERE LOWER(TRIM(r.cedula_representante)) = LOWER(TRIM(?)) 
               OR LOWER(TRIM(r.cedula_representante)) = LOWER(TRIM(?))
        ");
        $stmt_rep->execute([$cedula_rep, 'V-'.$cedula_rep]);
        $representados = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

        
        if (empty($representados)) {
            $stmt_fallback = $pdo->query("SELECT id, cedula, nombre, apellido, nivel_academico, seccion FROM estudiantes LIMIT 3");
            $representados = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
        }

        
        $id_est_req = $_GET['id_estudiante'] ?? ($representados[0]['id'] ?? null);
        if ($id_est_req) {
            foreach ($representados as $rep) {
                if ($rep['id'] == $id_est_req) {
                    $estudiante_seleccionado = $rep;
                    break;
                }
            }
            if (!$estudiante_seleccionado && !empty($representados)) {
                $estudiante_seleccionado = $representados[0];
            }
        }

        
        if ($estudiante_seleccionado) {
            $id_est = $estudiante_seleccionado['id'];
            $stmt_cal = $pdo->prepare("
                SELECT c.id, c.lapso, c.nota, c.materia, ev.titulo, ev.tipo, ev.ponderacion
                FROM calificaciones c
                LEFT JOIN evaluaciones ev ON c.evaluacion_id = ev.id
                WHERE c.estudiante_id = ?
                ORDER BY c.materia ASC, c.lapso ASC
            ");
            $stmt_cal->execute([$id_est]);
            $todas_las_notas = $stmt_cal->fetchAll(PDO::FETCH_ASSOC);

            foreach ($todas_las_notas as $row) {
                $mat_nombre = !empty($row['materia']) ? $row['materia'] : 'General';
                $lap = $row['lapso'] ?? 1;
                $notas_por_materia[$mat_nombre][$lap][] = $row;
            }
        }

    } catch (PDOException $e) {
        $error_db = "Error al consultar representados y notas: " . $e->getMessage();
    }
}

$lapso_filtro = isset($_GET['lapso']) ? (int)$_GET['lapso'] : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Representantes - Escuela Técnica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .slide-up { animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideUp { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    
    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center gap-4 border-b border-slate-800">
            <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-600/30 text-white shrink-0">
                <i class="fa-solid fa-user-shield text-2xl"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Portal Académico</h1>
                <p class="text-xs text-emerald-400 font-medium">Representantes</p>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Navegación</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>
            <a href="representante.php" class="flex items-center gap-3 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl shadow-md transition-all">
                <i class="fa-solid fa-graduation-cap w-5"></i> Mis Representados
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Consulta de Calificaciones</h2>
                    <p class="text-xs text-slate-500">Avance académico por asignatura y lapso escolar</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3.5 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-emerald-600"></i> C.I: <?= htmlspecialchars($cedula_rep) ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-6">

                <?php if (!empty($error_db)): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl flex items-center gap-3 shadow-sm slide-up">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-2xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($error_db); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($representados)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider"><i class="fa-solid fa-child mr-1"></i> Representados:</span>
                            <?php foreach ($representados as $rep): ?>
                                <a href="?id_estudiante=<?= $rep['id'] ?>&lapso=<?= $lapso_filtro ?>" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($estudiante_seleccionado && $estudiante_seleccionado['id'] == $rep['id']) ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                                    <i class="fa-solid fa-user-graduate text-xs"></i>
                                    <?= htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']) ?>
                                    <span class="text-[10px] opacity-80 border-l border-white/30 pl-2"><?= htmlspecialchars(($rep['nivel_academico'] ?? 'Nivel') . ' - ' . $rep['seccion']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Lapso:</span>
                            <?php for($l=1; $l<=3; $l++): ?>
                                <a href="?id_estudiante=<?= $estudiante_seleccionado['id'] ?? 0 ?>&lapso=<?= $l ?>" class="px-3.5 py-1.5 rounded-lg text-xs font-black transition-all <?= ($lapso_filtro === $l) ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    Lapso <?= $l ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($estudiante_seleccionado): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-wrap justify-between items-center gap-4 slide-up">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-extrabold text-xl border border-emerald-100">
                                <?= mb_substr($estudiante_seleccionado['nombre'], 0, 1) . mb_substr($estudiante_seleccionado['apellido'], 0, 1) ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($estudiante_seleccionado['nombre'] . ' ' . $estudiante_seleccionado['apellido']) ?></h3>
                                <div class="flex gap-2 items-center text-xs text-slate-500 mt-1">
                                    <span>C.I: <strong><?= htmlspecialchars($estudiante_seleccionado['cedula']) ?></strong></span>
                                    <span>•</span>
                                    <span class="px-2 py-0.5 bg-slate-100 font-bold rounded text-slate-700"><?= htmlspecialchars(($estudiante_seleccionado['nivel_academico'] ?? 'Grado') . ' - Sección ' . $estudiante_seleccionado['seccion']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 bg-slate-50 px-4 py-3 rounded-xl border border-slate-200">
                            <i class="fa-solid fa-circle-info text-emerald-600"></i>
                            <span class="text-xs font-medium text-slate-600">Consultando notas correspondientes al <strong>Lapso <?= $lapso_filtro ?></strong></span>
                        </div>
                    </div>

                    <?php if (empty($notas_por_materia)): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-400 slide-up">
                            <i class="fa-solid fa-folder-open text-4xl mb-3 text-slate-300"></i>
                            <h4 class="font-bold text-slate-700 text-base">Sin Calificaciones Registradas</h4>
                            <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Aún no se han publicado calificaciones para este estudiante en el sistema.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-6 slide-up">
                            <?php foreach ($notas_por_materia as $materia_nombre => $lapsos_data): ?>
                                <?php $notas_lapso_actual = $lapsos_data[$lapso_filtro] ?? []; ?>
                                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                                    <div class="p-4 bg-slate-900 text-white flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
                                                <i class="fa-solid fa-book"></i>
                                            </div>
                                            <h4 class="font-bold text-base tracking-wide"><?= htmlspecialchars($materia_nombre) ?></h4>
                                        </div>
                                        <span class="text-xs font-bold bg-slate-800 text-slate-300 px-3 py-1 rounded-lg border border-slate-700">
                                            Lapso <?= $lapso_filtro ?>
                                        </span>
                                    </div>

                                    <div class="p-5">
                                        <?php if (empty($notas_lapso_actual)): ?>
                                            <p class="text-xs font-semibold text-slate-400 italic py-2">Sin notas registradas para <?= htmlspecialchars($materia_nombre) ?> en el Lapso <?= $lapso_filtro ?>.</p>
                                        <?php else: ?>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-sm">
                                                    <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold">
                                                        <tr>
                                                            <th class="px-4 py-2.5">Evaluación</th>
                                                            <th class="px-4 py-2.5">Tipo</th>
                                                            <th class="px-4 py-2.5 text-center">Ponderación</th>
                                                            <th class="px-4 py-2.5 text-center">Nota (0-20)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <?php 
                                                            $suma_ponderada = 0;
                                                            $total_ponderacion = 0;
                                                            foreach ($notas_lapso_actual as $item): 
                                                                $nota_num = (float)$item['nota'];
                                                                $pond = (float)($item['ponderacion'] ?? 0);
                                                                if($pond > 0) {
                                                                    $suma_ponderada += ($nota_num * ($pond / 100));
                                                                    $total_ponderacion += $pond;
                                                                }
                                                        ?>
                                                            <tr class="hover:bg-slate-50 transition-colors">
                                                                <td class="px-4 py-3 font-semibold text-slate-800">
                                                                    <?= htmlspecialchars($item['titulo'] ?? 'Evaluación Regular') ?>
                                                                </td>
                                                                <td class="px-4 py-3 text-xs text-slate-500">
                                                                    <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold">
                                                                        <?= htmlspecialchars($item['tipo'] ?? 'Actividad') ?>
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-center text-xs font-bold text-slate-600">
                                                                    <?= $pond > 0 ? $pond . '%' : 'N/A' ?>
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="inline-block px-3 py-1 rounded-xl text-xs font-black <?= $nota_num >= 10 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                                                        <?= sprintf("%.1f", $nota_num) ?> / 20
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <?php if ($total_ponderacion > 0): ?>
                                                <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end items-center gap-3 text-xs">
                                                    <span class="font-bold text-slate-500 uppercase">Acumulado Estimado:</span>
                                                    <span class="px-3 py-1 rounded-xl font-black text-sm <?= $suma_ponderada >= 10 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                                        <?= sprintf("%.2f", $suma_ponderada) ?> / 20
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-400 slide-up">
                        <i class="fa-solid fa-user-slash text-4xl mb-3 text-amber-500"></i>
                        <h4 class="font-bold text-slate-700 text-base">No hay representados vinculados</h4>
                        <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Su Cédula (<strong><?= htmlspecialchars($cedula_rep) ?></strong>) no aparece vinculada a ningún estudiante. Solicite al docente o a Coordinación el registro de vinculación.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>
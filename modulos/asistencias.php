<?php
/**
 * Módulo de Asistencia por Código QR y Control Docente
 * Permite al profesor proyectar el QR, registrar manualmente, y editar el estado
 * de asistencia (Presente, Tarde, Ausente) directamente desde la tabla.
 */
session_start();


ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

if (!isset($_SESSION['usuario_logueado']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = strtolower(trim($_SESSION['rol'] ?? $_SESSION['usuario_rol'] ?? 'profesor'));
$roles_permitidos = ['administrador', 'admin', 'profesor', 'docente', 'estudiante'];

if (!in_array($rol_actual, $roles_permitidos)) {
    header('Location: ../index.php?error=acceso_denegado');
    exit;
}


$usuario_profesor = '';
$posibles_variables = ['username', 'usuario', 'nombre_usuario', 'user', 'id_usuario', 'id', 'cedula', 'email'];
foreach ($posibles_variables as $var) {
    if (!empty($_SESSION[$var])) {
        $usuario_profesor = $_SESSION[$var];
        break;
    }
}
if (empty($usuario_profesor)) {
    $usuario_profesor = 'jose_paez';
}

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
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

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS asistencias_qr (
        id INT AUTO_INCREMENT PRIMARY KEY,
        estudiante_id INT NOT NULL,
        seccion VARCHAR(50) NOT NULL,
        materia VARCHAR(100) NOT NULL,
        profesor VARCHAR(100) NOT NULL,
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        estado VARCHAR(20) DEFAULT 'Presente',
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_asistencia_dia (estudiante_id, seccion, materia, fecha),
        INDEX(estudiante_id),
        INDEX(seccion),
        INDEX(materia)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch(PDOException $e) {
    $error_db = "Fallo de conexión a la Base de Datos: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    header('Content-Type: application/json');

    
    if (isset($_POST['registrar_asistencia_qr'])) {
        try {
            $cedula_estudiante = trim($_POST['cedula_estudiante'] ?? '');
            $seccion_sel = trim($_POST['seccion'] ?? '');
            $materia_sel = trim($_POST['materia'] ?? '');
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            if (empty($cedula_estudiante) || empty($seccion_sel) || empty($materia_sel)) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros obligatorios.']);
                exit;
            }

            $stmt_est = $pdo->prepare("SELECT id, nombre, apellido, seccion FROM estudiantes WHERE TRIM(LOWER(cedula)) = TRIM(LOWER(?)) LIMIT 1");
            $stmt_est->execute([$cedula_estudiante]);
            $estudiante = $stmt_est->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                echo json_encode(['status' => 'error', 'message' => 'Estudiante no encontrado con la cédula provista.']);
                exit;
            }

            
            $estado_asistencia = 'Presente';
            $tiempo_actual = strtotime($hora_actual);
            $limite_a_tiempo = strtotime('07:45:00');
            $fin_jornada = strtotime('12:00:00');

            if ($tiempo_actual > $limite_a_tiempo && $tiempo_actual <= $fin_jornada) {
                $estado_asistencia = 'Tarde';
            } elseif ($tiempo_actual > $fin_jornada) {
                $estado_asistencia = 'Tarde';
            }

            $stmt_asis = $pdo->prepare("
                INSERT INTO asistencias_qr (estudiante_id, seccion, materia, profesor, fecha, hora, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE hora = VALUES(hora), estado = VALUES(estado)
            ");
            $stmt_asis->execute([$estudiante['id'], $seccion_sel, $materia_sel, $usuario_profesor, $fecha_actual, $hora_actual, $estado_asistencia]);

            echo json_encode([
                'status' => 'success',
                'message' => '¡Asistencia registrada con éxito!',
                'estudiante' => $estudiante['apellido'] . ', ' . $estudiante['nombre'],
                'estudiante_id' => $estudiante['id'],
                'estado' => $estado_asistencia,
                'hora' => $hora_actual
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en BD: ' . $e->getMessage()]);
            exit;
        }
    }

    
    if (isset($_POST['actualizar_estado_asistencia'])) {
        try {
            $estudiante_id = intval($_POST['estudiante_id'] ?? 0);
            $nuevo_estado = trim($_POST['nuevo_estado'] ?? 'Presente');
            $seccion_sel = trim($_POST['seccion'] ?? '');
            $materia_sel = trim($_POST['materia'] ?? '');
            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            if ($estudiante_id <= 0 || empty($seccion_sel) || empty($materia_sel)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para la actualización.']);
                exit;
            }

            if ($nuevo_estado === 'Ausente') {
                $stmt_del = $pdo->prepare("DELETE FROM asistencias_qr WHERE estudiante_id = ? AND seccion = ? AND materia = ? AND fecha = ?");
                $stmt_del->execute([$estudiante_id, $seccion_sel, $materia_sel, $fecha_actual]);
            } else {
                $stmt_upd = $pdo->prepare("
                    INSERT INTO asistencias_qr (estudiante_id, seccion, materia, profesor, fecha, hora, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE estado = VALUES(estado), hora = VALUES(hora)
                ");
                $stmt_upd->execute([$estudiante_id, $seccion_sel, $materia_sel, $usuario_profesor, $fecha_actual, $hora_actual, $nuevo_estado]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Estado actualizado a ' . $nuevo_estado,
                'nuevo_estado' => $nuevo_estado,
                'hora' => $hora_actual
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error BD: ' . $e->getMessage()]);
            exit;
        }
    }
}


$mis_secciones = [];
$mis_materias = [];
$estudiantes_lista = [];
$asistencias_hoy = [];

$seccion_activa = $_GET['seccion'] ?? '';
$materia_activa = $_GET['materia'] ?? '';

if ($pdo && !empty($usuario_profesor)) {
    try {
        $stmt_u = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? OR id = ? OR cedula = ? OR email = ? LIMIT 1");
        $id_val = is_numeric($usuario_profesor) ? (int)$usuario_profesor : 0;
        $stmt_u->execute([$usuario_profesor, $id_val, $usuario_profesor, $usuario_profesor]);
        $real_username = $stmt_u->fetchColumn();
        if ($real_username) { $usuario_profesor = $real_username; }

        $stmt_sec = $pdo->prepare("SELECT DISTINCT seccion FROM asignaciones_docentes WHERE LOWER(TRIM(usuario_profesor)) = LOWER(TRIM(?)) ORDER BY seccion ASC");
        $stmt_sec->execute([$usuario_profesor]);
        $mis_secciones = array_map('trim', $stmt_sec->fetchAll(PDO::FETCH_COLUMN));

        $stmt_mat = $pdo->prepare("SELECT DISTINCT materia FROM asignaciones_materias WHERE LOWER(TRIM(usuario_profesor)) = LOWER(TRIM(?)) ORDER BY materia ASC");
        $stmt_mat->execute([$usuario_profesor]);
        $mis_materias = array_map('trim', $stmt_mat->fetchAll(PDO::FETCH_COLUMN));

        if (empty($mis_secciones)) { $mis_secciones = ['1A']; }
        if (empty($mis_materias)) { $mis_materias = ['Matemática']; }

        if (empty($seccion_activa) || !in_array($seccion_activa, $mis_secciones)) {
            $seccion_activa = $mis_secciones[0];
        }
        if (empty($materia_activa) || !in_array($materia_activa, $mis_materias)) {
            $materia_activa = $mis_materias[0];
        }

        preg_match('/(\d+)?\s*([A-Za-z]+)/', $seccion_activa, $matches);
        $sec_num = $matches[1] ?? '';
        $sec_letra = $matches[2] ?? $seccion_activa;

        $stmt_est = $pdo->prepare("
            SELECT DISTINCT e.id, e.cedula, e.nombre, e.apellido, e.nivel_academico, e.seccion
            FROM estudiantes e
            INNER JOIN asignaciones_estudiantes_materias aem ON e.id = aem.estudiante_id
            WHERE (e.estado = 'Activo' OR e.estado IS NULL OR e.estado = '')
              AND TRIM(LOWER(aem.materia)) = TRIM(LOWER(:materia))
              AND (
                   TRIM(LOWER(e.seccion)) = TRIM(LOWER(:seccion_completa))
                   OR TRIM(LOWER(e.seccion)) = TRIM(LOWER(:sec_letra))
                   OR e.seccion LIKE CONCAT('%', :sec_letra, '%')
              )
              AND (
                   :sec_num = '' 
                   OR e.nivel_academico LIKE CONCAT('%', :sec_num, '%')
                   OR e.nivel_academico LIKE CONCAT('%', 
                       CASE :sec_num 
                           WHEN '1' THEN '1er' 
                           WHEN '2' THEN '2do' 
                           WHEN '3' THEN '3er' 
                           WHEN '4' THEN '4to' 
                           WHEN '5' THEN '5to' 
                           WHEN '6' THEN '6to' 
                           ELSE :sec_num 
                       END, '%')
              )
            ORDER BY e.apellido ASC, e.nombre ASC
        ");
        $stmt_est->execute([
            ':materia'          => $materia_activa,
            ':seccion_completa' => $seccion_activa,
            ':sec_letra'        => $sec_letra,
            ':sec_num'          => $sec_num
        ]);
        $estudiantes_lista = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

        $fecha_hoy = date('Y-m-d');
        $stmt_asis = $pdo->prepare("
            SELECT estudiante_id, hora, estado 
            FROM asistencias_qr 
            WHERE seccion = ? AND materia = ? AND fecha = ?
        ");
        $stmt_asis->execute([$seccion_activa, $materia_activa, $fecha_hoy]);
        $res_asis = $stmt_asis->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res_asis as $a) {
            $asistencias_hoy[$a['estudiante_id']] = $a;
        }

    } catch (PDOException $e) {
        $error_db = "Error al consultar asignaciones: " . $e->getMessage();
    }
}


$qr_payload = json_encode([
    'token'    => md5($seccion_activa . $materia_activa . $usuario_profesor . date('Y-m-d')),
    'seccion'  => $seccion_activa,
    'materia'  => $materia_activa,
    'profesor' => $usuario_profesor,
    'fecha'    => date('Y-m-d'),
    'expira'   => time() + 7200
]);

$nombre_sesion_actual = $_SESSION['nombre'] ?? $usuario_profesor;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asistencia QR - <?= htmlspecialchars($nombre_institucion); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #0f172a; 
            --primary-light: #1e293b; 
            --accent: #f59e0b; 
            --accent-hover: #d97706;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            margin: 0; 
            color: var(--text-main); 
            -webkit-font-smoothing: antialiased;
        }

        .slide-up { animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 100; max-width: 400px; }
        .toast { padding: 12px 18px; border-radius: 12px; color: white; font-weight: 600; font-size: 13px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; margin-bottom: 10px; animation: slideDown 0.3s ease; }
        .toast.success { background: #10b981; border: 1px solid #059669; }
        .toast.error { background: #ef4444; border: 1px solid #dc2626; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 sm:p-6">

    <div id="toast-container"></div>

   
    <header class="flex items-center justify-between bg-slate-900 text-white p-4 sm:p-5 rounded-2xl shadow-lg max-w-7xl mx-auto w-full mb-6 border border-slate-800">
        <div class="flex items-center gap-4">
            <a href="../index.php" class="w-11 h-11 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl flex items-center justify-center transition-all border border-slate-700">
                <i class="fa-solid fa-arrow-left text-base"></i>
            </a>
            <div>
                <h1 class="font-bold text-base sm:text-lg text-white">Control de Asistencia Digital</h1>
                <p class="text-xs text-amber-400 font-medium">Docente: <?= htmlspecialchars($nombre_sesion_actual) ?> &bull; Sección: <span class="text-white font-bold"><?= htmlspecialchars($seccion_activa) ?></span> &bull; Materia: <span class="text-white font-bold"><?= htmlspecialchars($materia_activa) ?></span></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3.5 py-1.5 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded-xl border border-emerald-500/30 hidden sm:inline-flex items-center gap-1.5">
                <i class="fa-solid fa-database"></i> <?= htmlspecialchars($status_db) ?>
            </span>
        </div>
    </header>

    
    <main class="max-w-7xl mx-auto w-full space-y-6 flex-1">

        <?php if ($error_db): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm slide-up">
                <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xl"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($error_db); ?></span>
            </div>
        <?php endif; ?>

        
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm slide-up space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-book-bookmark text-amber-500 mr-1"></i> Asignatura Actual
                </label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($mis_materias as $m): ?>
                        <a href="?seccion=<?= urlencode($seccion_activa) ?>&materia=<?= urlencode($m) ?>" 
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all border flex items-center gap-2 <?= $materia_activa === $m ? 'bg-slate-900 text-white border-slate-900 shadow-md' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                            <i class="fa-solid fa-book"></i> <?= htmlspecialchars($m) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-users text-amber-500 mr-1"></i> Mis Secciones Asignadas
                </label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($mis_secciones as $sec): ?>
                        <a href="?seccion=<?= urlencode($sec) ?>&materia=<?= urlencode($materia_activa) ?>" 
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all border flex items-center gap-2 <?= $seccion_activa === $sec ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-md' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' ?>">
                            <i class="fa-solid fa-graduation-cap"></i> Sección <?= htmlspecialchars($sec) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            
            <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm slide-up space-y-5 flex flex-col justify-between">
                
                <div class="space-y-4 text-center py-2">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h3 class="font-bold text-slate-800 text-base">QR de Asistencia en Vivo</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Tolerancia hasta las 07:45 AM</p>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border-2 border-dashed border-amber-400 inline-block shadow-inner">
                        <div id="qr_proyector_container" class="flex items-center justify-center min-h-[200px]"></div>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-left space-y-1.5 text-xs text-slate-600">
                        <p><b>Materia:</b> <?= htmlspecialchars($materia_activa) ?></p>
                        <p><b>Sección:</b> <?= htmlspecialchars($seccion_activa) ?></p>
                        <p><b>Fecha:</b> <?= date('d/m/Y') ?></p>
                    </div>
                </div>

                <div id="resultado_scan" class="hidden p-3 rounded-xl text-xs font-bold text-center"></div>

                
                <div class="pt-3 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Registro Manual (Cédula):</label>
                    <div class="flex gap-2">
                        <input type="text" id="manual_cedula" placeholder="Ej: V-30123456" class="flex-1 px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <button onclick="registrarManual()" class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md transition-all">
                            Registrar
                        </button>
                    </div>
                </div>

            </div>

            
            <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden slide-up flex flex-col">
                <div class="p-5 bg-slate-50 border-b border-slate-200 flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">
                            Estudiantes (Sección <?= htmlspecialchars($seccion_activa) ?>)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Inscritos: <b class="text-slate-800"><?= count($estudiantes_lista) ?></b> | Registrados hoy: <b class="text-emerald-600" id="contador_asistencias"><?= count($asistencias_hoy) ?></b>
                        </p>
                    </div>
                </div>

                <?php if (empty($estudiantes_lista)): ?>
                    <div class="p-12 text-center text-slate-400 my-auto">
                        <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-300"></i>
                        <p class="font-semibold text-slate-700 text-base">No hay estudiantes en la Sección <?= htmlspecialchars($seccion_activa) ?> para <?= htmlspecialchars($materia_activa) ?>.</p>
                        <p class="text-xs mt-1 text-slate-500">Asegúrese de asignar esta asignatura a los alumnos en Gestión de Personas.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3.5">Estudiante</th>
                                    <th class="px-5 py-3.5">Cédula</th>
                                    <th class="px-5 py-3.5 text-center">Estado / Editar</th>
                                    <th class="px-5 py-3.5 text-center">QR</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php foreach ($estudiantes_lista as $est): ?>
                                    <?php 
                                        $asis = $asistencias_hoy[$est['id']] ?? null; 
                                        $estado_actual = $asis['estado'] ?? 'Ausente';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors" id="fila_est_<?= $est['id'] ?>">
                                        <td class="px-5 py-3.5 font-semibold text-slate-800">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 font-bold flex items-center justify-center shrink-0 border border-amber-200 text-xs">
                                                    <?= mb_substr($est['nombre'], 0, 1) . mb_substr($est['apellido'], 0, 1) ?>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre']) ?></p>
                                                    <p class="text-[10px] text-slate-500"><?= htmlspecialchars($est['nivel_academico'] ?? 'Estudiante') ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-600 text-xs font-mono font-medium">
                                            <?= htmlspecialchars($est['cedula']) ?>
                                        </td>
                                        <td class="px-5 py-3.5 text-center" id="col_estado_<?= $est['id'] ?>">
                                            <div class="inline-flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200">
                                                <button onclick="cambiarEstado(<?= $est['id'] ?>, 'Presente')" title="Marcar Presente" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all <?= $estado_actual === 'Presente' ? 'bg-emerald-600 text-white shadow' : 'text-slate-600 hover:text-slate-900' ?>">
                                                    Pres.
                                                </button>
                                                <button onclick="cambiarEstado(<?= $est['id'] ?>, 'Tarde')" title="Marcar Tarde" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all <?= $estado_actual === 'Tarde' ? 'bg-amber-500 text-slate-950 shadow' : 'text-slate-600 hover:text-slate-900' ?>">
                                                    Tarde
                                                </button>
                                                <button onclick="cambiarEstado(<?= $est['id'] ?>, 'Ausente')" title="Marcar Ausente" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all <?= $estado_actual === 'Ausente' ? 'bg-rose-600 text-white shadow' : 'text-slate-600 hover:text-slate-900' ?>">
                                                    Aus.
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <button onclick="generarQRModal('<?= htmlspecialchars($est['cedula']) ?>', '<?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?>')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl font-bold text-[11px] transition-all border border-slate-200 inline-flex items-center gap-1">
                                                <i class="fa-solid fa-qrcode text-amber-600"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <footer class="text-center text-[11px] text-slate-500 py-4">
        Escuela Técnica "Pedro García Leal" &bull; Control de Asistencia QR
    </footer>

    
    <div id="modalQR" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden p-6 border border-slate-200 slide-up text-center space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-sm" id="modal_qr_nombre">Carnet QR Estudiante</h3>
                <button type="button" onclick="cerrarModalQR()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div id="qrcode_box" class="flex items-center justify-center py-4 bg-slate-50 rounded-2xl border border-slate-200"></div>
            <p class="text-xs text-slate-500 font-mono font-bold" id="modal_qr_cedula"></p>
            <button onclick="cerrarModalQR()" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs shadow-md transition-all">Cerrar</button>
        </div>
    </div>

    <script>
        const seccionActiva = <?= json_encode($seccion_activa) ?>;
        const materiaActiva = <?= json_encode($materia_activa) ?>;
        const qrPayload = <?= json_encode($qr_payload) ?>;

        window.addEventListener('DOMContentLoaded', () => {
            const containerProyector = document.getElementById('qr_proyector_container');
            if (containerProyector) {
                new QRCode(containerProyector, {
                    text: qrPayload,
                    width: 170,
                    height: 170,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            }
        });

        function showToast(mensaje, tipo = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            toast.innerHTML = `<i class="fa-solid ${tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'} text-base"></i> <div>${mensaje}</div>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function enviarAsistencia(cedula) {
            const formData = new FormData();
            formData.append('registrar_asistencia_qr', '1');
            formData.append('cedula_estudiante', cedula);
            formData.append('seccion', seccionActiva);
            formData.append('materia', materiaActiva);

            fetch('asistencias.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(`<strong>${data.estudiante}</strong><br>Asistencia registrada (${data.estado})`, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => showToast('Error al conectar con el servidor.', 'error'));
        }

        function cambiarEstado(estudianteId, nuevoEstado) {
            const formData = new FormData();
            formData.append('actualizar_estado_asistencia', '1');
            formData.append('estudiante_id', estudianteId);
            formData.append('nuevo_estado', nuevoEstado);
            formData.append('seccion', seccionActiva);
            formData.append('materia', materiaActiva);

            fetch('asistencias.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => showToast('Error al actualizar estado.', 'error'));
        }

        function registrarManual() {
            const cedula = document.getElementById('manual_cedula').value.trim();
            if (cedula) {
                enviarAsistencia(cedula);
                document.getElementById('manual_cedula').value = '';
            }
        }

        function generarQRModal(cedula, nombre) {
            document.getElementById('modal_qr_nombre').innerText = nombre;
            document.getElementById('modal_qr_cedula').innerText = cedula;
            const container = document.getElementById('qrcode_box');
            container.innerHTML = '';
            new QRCode(container, {
                text: cedula,
                width: 150,
                height: 150
            });
            const modal = document.getElementById('modalQR');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalQR() {
            const modal = document.getElementById('modalQR');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['usuario_logueado'])) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = strtolower(trim($_SESSION['rol'] ?? $_SESSION['usuario_rol'] ?? 'estudiante'));
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['username'] ?? 'Estudiante';
$user_id = $_SESSION['user_id'] ?? null;

$host = 'localhost';
$dbname = 'escuela_tecnica';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}


$hora_limite_entrada = $_SESSION['hora_limite_asistencia'] ?? '07:30:00';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'escaneo_estudiante_directo') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    $payload_str = trim($_POST['qr_payload'] ?? '');
    $datos_qr = json_decode($payload_str, true);

    if (!$datos_qr || !isset($datos_qr['token']) || !isset($datos_qr['seccion']) || !isset($datos_qr['materia']) || !isset($datos_qr['expira'])) {
        echo json_encode(['success' => false, 'mensaje' => 'El código QR no es válido o no pertenece al sistema.']);
        exit;
    }

    if (time() > $datos_qr['expira']) {
        echo json_encode(['success' => false, 'mensaje' => 'El código QR ha expirado. Pide al profesor que lo actualice.']);
        exit;
    }

    try {
        $stmt_est = $pdo->prepare("
            SELECT e.id, e.nombre, e.apellido, e.cedula, e.seccion 
            FROM estudiantes e 
            LEFT JOIN usuarios u ON (u.cedula = e.cedula OR u.email = e.email)
            WHERE u.id = ? OR e.cedula = ? OR u.username = ?
            LIMIT 1
        ");
        $stmt_est->execute([$user_id, $_SESSION['username'] ?? '', $_SESSION['username'] ?? '']);
        $estudiante = $stmt_est->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            echo json_encode([
                'success' => false, 
                'mensaje' => 'No se encontró un perfil de estudiante vinculado a esta cuenta.'
            ]);
            exit;
        }

        $est_id = $estudiante['id'];
        $nombre_completo = trim($estudiante['nombre'] . ' ' . $estudiante['apellido']);
        $seccion_estudiante = $datos_qr['seccion'];
        $materia_qr = $datos_qr['materia'];
        $profesor_qr = $datos_qr['profesor'] ?? 'Sistema';
        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');

        
        $tiempo_actual = strtotime($hora_actual);
        $limite_a_tiempo = strtotime('07:45:00');
        $fin_jornada = strtotime('12:00:00');

        $estado_asistencia = 'Presente';
        if ($tiempo_actual > $limite_a_tiempo && $tiempo_actual <= $fin_jornada) {
            $estado_asistencia = 'Tarde';
        } elseif ($tiempo_actual > $fin_jornada) {
            $estado_asistencia = 'Tarde';
        }

        $stmt_ins = $pdo->prepare("
            INSERT INTO asistencias_qr (estudiante_id, seccion, materia, profesor, fecha, hora, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE hora = VALUES(hora), estado = VALUES(estado)
        ");
        $stmt_ins->execute([
            $est_id, 
            $seccion_estudiante, 
            $materia_qr, 
            $profesor_qr, 
            $fecha_actual, 
            $hora_actual, 
            $estado_asistencia
        ]);

        echo json_encode([
            'success' => true,
            'estudiante' => $nombre_completo,
            'estado' => $estado_asistencia,
            'hora' => $hora_actual,
            'mensaje' => "¡Asistencia registrada con éxito ($estado_asistencia)!"
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error BD: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear Asistencia - E.T. Pedro Garcia Leal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .glow-effect { box-shadow: 0 0 25px rgba(16, 185, 129, 0.2); }
        #toast-container { position: fixed; top: 20px; right: 20px; left: 20px; z-index: 100; max-width: 400px; margin: auto; }
        .toast { padding: 14px 20px; border-radius: 16px; color: white; font-weight: 600; font-size: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .toast.success { background: #10b981; border: 1px solid #059669; }
        .toast.error { background: #ef4444; border: 1px solid #dc2626; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="min-h-screen text-slate-100 flex flex-col justify-between p-4 sm:p-6">

    <header class="flex items-center justify-between bg-slate-900/80 backdrop-blur-md p-4 rounded-2xl border border-slate-800">
        <div class="flex items-center gap-3">
            <a href="../index.php" class="w-10 h-10 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl flex items-center justify-center transition-colors">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="font-bold text-sm text-white">Escanear Asistencia</h1>
                <p class="text-[11px] text-emerald-400 font-medium">Estudiante: <?= htmlspecialchars($nombre_usuario) ?></p>
            </div>
        </div>
        <div class="w-9 h-9 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center font-bold text-xs">
            <i class="fa-solid fa-qrcode text-lg"></i>
        </div>
    </header>

    <div id="toast-container"></div>

    <main class="my-auto max-w-md mx-auto w-full space-y-4">
        
        <div class="bg-slate-900 rounded-3xl p-5 border border-slate-800 glow-effect text-center space-y-4">
            <div>
                <h2 class="text-lg font-bold text-white">Apunta al Código QR</h2>
                <p class="text-xs text-slate-400">Escanea el código proyectado por el profesor</p>
            </div>

            <div id="reader-estudiante" class="w-full rounded-2xl overflow-hidden border-2 border-dashed border-emerald-500/40 bg-slate-950 min-h-[250px] flex flex-col justify-center items-center"></div>

            <div class="text-[11px] text-slate-400 bg-slate-950 p-3 rounded-xl border border-slate-800 flex items-center justify-center gap-2">
                <i class="fa-solid fa-clock text-amber-400"></i>
                <span>Hora límite regular de entrada: <strong><?= htmlspecialchars($hora_limite_entrada) ?></strong></span>
            </div>
        </div>
    </main>

    <footer class="text-center text-[11px] text-slate-500 py-2">
        Escuela Técnica "Pedro García Leal" &bull; Control de Asistencia QR
    </footer>

    <script>
        let html5QrCode = null;

        function showToast(mensaje, tipo = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            toast.innerHTML = `<i class="fa-solid ${tipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'} text-lg"></i> <div>${mensaje}</div>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function iniciarScanner() {
            html5QrCode = new Html5Qrcode("reader-estudiante");
            
            const config = { 
                fps: 10, 
                qrbox: { width: 220, height: 220 } 
            };

            html5QrCode.start(
                { facingMode: "environment" }, 
                config, 
                onScanSuccess, 
                onScanFailure
            ).catch(err => {
                let scannerEstandar = new Html5QrcodeScanner(
                    "reader-estudiante",
                    { fps: 10, qrbox: { width: 220, height: 220 }, rememberLastUsedCamera: true },
                    false
                );
                scannerEstandar.render(onScanSuccess, onScanFailure);
            });
        }

        function onScanSuccess(decodedText) {
            const formData = new FormData();
            formData.append('action', 'escaneo_estudiante_directo');
            formData.append('qr_payload', decodedText);

            fetch('escanear-asistencia.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(`<strong>${data.estudiante}</strong><br>${data.mensaje}`, 'success');
                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop();
                    }
                    setTimeout(() => {
                        window.location.href = '../index.php';
                    }, 2200);
                } else {
                    showToast(data.mensaje, 'error');
                }
            })
            .catch(() => showToast('Error al conectar con el servidor.', 'error'));
        }

        function onScanFailure(error) {}

        document.addEventListener('DOMContentLoaded', iniciarScanner);
    </script>
</body>
</html>
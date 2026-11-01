<?php
/**
 * Módulo de Coordinación Académica - Asignación de Materias y Secciones a Docentes
 */
session_start();


$host = "localhost";
$dbname = "escuela_tecnica";
$user = "root";
$pass = "";


$mensaje_exito = "";
$error_db = "";
$status_db = "Desconectado";
$profesores = [];
$secciones_disponibles = [];
$asignaciones = [];
$asignaciones_materias = [];

$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['username'] ?? 'Coordinador Académico';
$rol_actual = $_SESSION['rol'] ?? $_SESSION['user_rol'] ?? 'coordinador';

$materias_disponibles = [
    'Matemática', 
    'Castellano', 
    'Biología', 
    'Física', 
    'Química', 
    'Historia Patria', 
    'Geografía', 
    'Inglés', 
    'Educación Física', 
    'Orientación y Convivencia', 
    'Arte y Patrimonio', 
    'Formación para la Soberanía'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_db = "Conectado de manera exitosa";
    
    
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

    $check_mats = $pdo->query("SELECT COUNT(*) FROM asignaciones_materias")->fetchColumn();
    if ($check_mats == 0) {
        $default_assignments = [
            'jose_paez' => ['Matemática', 'Castellano', 'Biología'],
            'ana_martinez' => ['Física', 'Química', 'Inglés'],
            'luis_fernandez' => ['Historia Patria', 'Geografía', 'Educación Física'],
            'carmen_santos' => ['Arte y Patrimonio', 'Orientación y Convivencia', 'Formación para la Soberanía']
        ];

        $stmt_init = $pdo->prepare("INSERT INTO asignaciones_materias (usuario_profesor, materia) VALUES (?, ?)");
        foreach ($default_assignments as $prof_user => $mats) {
            foreach ($mats as $m) {
                $stmt_init->execute([$prof_user, $m]);
            }
        }
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asignacion'])) {
        $prof_username = $_POST['prof_username'] ?? '';
        $secciones_seleccionadas = $_POST['secciones'] ?? [];
        $materias_seleccionadas = $_POST['materias'] ?? [];
        
        if (!empty($prof_username)) {
            $stmt = $pdo->prepare("DELETE FROM asignaciones_docentes WHERE usuario_profesor = ?");
            $stmt->execute([$prof_username]);

            $stmt_mat_del = $pdo->prepare("DELETE FROM asignaciones_materias WHERE usuario_profesor = ?");
            $stmt_mat_del->execute([$prof_username]);
            
            if (!empty($secciones_seleccionadas)) {
                $stmt = $pdo->prepare("INSERT INTO asignaciones_docentes (usuario_profesor, seccion) VALUES (?, ?)");
                foreach ($secciones_seleccionadas as $sec) {
                    $stmt->execute([$prof_username, $sec]);
                }
            }

            if (!empty($materias_seleccionadas)) {
                $stmt_mat = $pdo->prepare("INSERT INTO asignaciones_materias (usuario_profesor, materia) VALUES (?, ?)");
                foreach ($materias_seleccionadas as $mat) {
                    $stmt_mat->execute([$prof_username, $mat]);
                }
            }

            $mensaje_exito = "Las materias y secciones han sido asignadas correctamente a @" . htmlspecialchars($prof_username) . ".";
        }
    }
    
    
    try {
        $stmt = $pdo->query("SELECT username, nombre, apellido, email FROM usuarios WHERE LOWER(rol) LIKE '%profesor%'");
        $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $profesores = [];
    }

    
    if (empty($profesores)) {
        $profesores = [
            ['username' => 'jose_paez', 'nombre' => 'José', 'apellido' => 'Páez', 'email' => 'jpaez@liceo.edu'],
            ['username' => 'ana_martinez', 'nombre' => 'Ana', 'apellido' => 'Martínez', 'email' => 'amartinez@liceo.edu'],
            ['username' => 'luis_fernandez', 'nombre' => 'Luis', 'apellido' => 'Fernández', 'email' => 'lfernandez@liceo.edu'],
            ['username' => 'carmen_santos', 'nombre' => 'Carmen', 'apellido' => 'Santos', 'email' => 'csantos@liceo.edu']
        ];
    }

    
    try {
        $stmt_sec = $pdo->query("SELECT nombre FROM secciones WHERE estado = 'Activa' ORDER BY nombre ASC");
        $secciones_disponibles = $stmt_sec->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $secciones_disponibles = [];
    }
    
    if (empty($secciones_disponibles)) {
        $secciones_disponibles = ['1A', '1B', '2A', '2B', '3A', '4A', '5A'];
    }
    
    
    $stmt = $pdo->query("SELECT usuario_profesor, seccion FROM asignaciones_docentes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $asignaciones[$row['usuario_profesor']][] = $row['seccion'];
    }

    
    $stmt_mats = $pdo->query("SELECT usuario_profesor, materia FROM asignaciones_materias");
    while ($row = $stmt_mats->fetch(PDO::FETCH_ASSOC)) {
        $asignaciones_materias[$row['usuario_profesor']][] = $row['materia'];
    }
    
} catch(PDOException $e) {
    $error_db = "Error de conexión a la base de datos: " . $e->getMessage();
    $status_db = "Fallo de conexión";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinación Académica - Asignación de Materias y Secciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .slide-up { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes slideUp { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    
    <aside id="sidebar" class="w-72 bg-slate-900 text-white flex flex-col shadow-2xl z-30 transition-all duration-300 hidden lg:flex">
        <div class="p-6 flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/20 text-slate-900 shrink-0 font-bold">
                    <i class="fa-solid fa-graduation-cap text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xs font-bold leading-tight uppercase tracking-wider text-slate-300">Escuela Técnica</h1>
                    <p class="text-xs text-amber-400 font-medium">Coordinación</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-4 mb-2">Módulos</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors text-sm font-medium">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>
            
            <a href="coordinacion.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-950 font-bold rounded-xl shadow-md shadow-amber-500/10 transition-colors text-sm">
                <i class="fa-solid fa-chalkboard-user w-5"></i> Carga Académica
            </a>

        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 rounded-full bg-amber-500 text-slate-950 font-black flex items-center justify-center">
                    <?= mb_substr($nombre_usuario, 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($nombre_usuario); ?></p>
                    <p class="text-xs text-amber-400 capitalize">Coordinación Académica</p>
                </div>
            </div>
        </div>
    </aside>

    
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Coordinación Académica</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Asignación de asignaturas y secciones a los docentes registrados</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="px-3.5 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-database text-amber-500"></i> <?= htmlspecialchars($status_db) ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 custom-scrollbar">
            <div class="max-w-7xl mx-auto space-y-6">

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm slide-up">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($mensaje_exito) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_db)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm slide-up">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($error_db) ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-6 slide-up">
                    <?php foreach ($profesores as $prof): 
                        $username = $prof['username'];
                        $nombre_completo = $prof['nombre'] . ' ' . $prof['apellido'];
                        $asig_actuales = $asignaciones[$username] ?? [];
                        $materias_actuales = $asignaciones_materias[$username] ?? [];
                    ?>
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:border-amber-400 hover:shadow-md transition-all flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 font-bold flex items-center justify-center border border-amber-200 shrink-0">
                                    <?= mb_substr($prof['nombre'], 0, 1) . mb_substr($prof['apellido'], 0, 1) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h3 class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($nombre_completo) ?></h3>
                                    <p class="text-xs text-slate-500 font-medium">@<?= htmlspecialchars($username) ?></p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs">
                                <div>
                                    <span class="font-bold text-slate-500 text-[10px] uppercase block mb-1">Materias actual:</span>
                                    <div class="flex flex-wrap gap-1">
                                        <?php if(empty($materias_actuales)): ?>
                                            <span class="text-rose-500 italic text-[11px]">Ninguna asignada</span>
                                        <?php else: ?>
                                            <?php foreach($materias_actuales as $m): ?>
                                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 font-bold rounded text-[10px]"><?= htmlspecialchars($m) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div>
                                    <span class="font-bold text-slate-500 text-[10px] uppercase block mb-1">Secciones actual:</span>
                                    <div class="flex flex-wrap gap-1">
                                        <?php if(empty($asig_actuales)): ?>
                                            <span class="text-rose-500 italic text-[11px]">Ninguna asignada</span>
                                        <?php else: ?>
                                            <?php foreach($asig_actuales as $s): ?>
                                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded text-[10px]"><?= htmlspecialchars($s) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-100 mb-4">

                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="prof_username" value="<?= htmlspecialchars($username) ?>">
                                
                                <div>
                                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-book text-blue-500"></i> Materias
                                    </h4>
                                    <div class="grid grid-cols-1 gap-1.5 max-h-36 overflow-y-auto pr-1 custom-scrollbar">
                                        <?php foreach ($materias_disponibles as $materia): ?>
                                            <label class="flex items-center gap-2 p-1.5 rounded-lg border <?= in_array($materia, $materias_actuales) ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-slate-50' ?> cursor-pointer text-xs font-medium text-slate-700">
                                                <input type="checkbox" name="materias[]" value="<?= htmlspecialchars($materia) ?>" 
                                                       class="w-3.5 h-3.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                                                       <?= in_array($materia, $materias_actuales) ? 'checked' : '' ?>>
                                                <span class="truncate"><?= htmlspecialchars($materia) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-users text-amber-500"></i> Secciones
                                    </h4>
                                    <div class="grid grid-cols-2 gap-1.5 max-h-28 overflow-y-auto pr-1 custom-scrollbar">
                                        <?php foreach ($secciones_disponibles as $sec): ?>
                                            <label class="flex items-center gap-2 p-1.5 rounded-lg border <?= in_array($sec, $asig_actuales) ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-slate-50' ?> cursor-pointer text-xs font-medium text-slate-700">
                                                <input type="checkbox" name="secciones[]" value="<?= htmlspecialchars($sec) ?>" 
                                                       class="w-3.5 h-3.5 text-amber-500 rounded border-slate-300 focus:ring-amber-500"
                                                       <?= in_array($sec, $asig_actuales) ? 'checked' : '' ?>>
                                                <span><?= htmlspecialchars($sec) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" name="guardar_asignacion" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-xl transition-all shadow-md text-xs flex justify-center items-center gap-2 mt-4">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Asignación
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
        }
    </script>
</body>
</html>
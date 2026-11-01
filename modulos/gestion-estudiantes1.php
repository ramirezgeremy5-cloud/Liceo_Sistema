<?php
session_start();

// Validar que exista una sesión activa
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_logueado'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? $_SESSION['usuario_logueado'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'Administrador';

// Parámetros de conexión a la base de datos local
$host = 'localhost';
$dbname = 'escuela_tecnica';
$db_user = 'root';
$db_pass = '';
$db_connected = false;
$error_con = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    $error_con = $e->getMessage();
}

// Variables para manejo de mensajes (flash session)
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$action = $_GET['action'] ?? 'list';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// PROCESAR FORMULARIOS (CREAR, EDITAR, ELIMINAR E IMPORTAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- IMPORTAR DESDE EXCEL (CSV) ---
    if (isset($_POST['import_students'])) {
        if ($db_connected && isset($_FILES['file_import']) && $_FILES['file_import']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['file_import']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION));

            if ($file_ext === 'csv') {
                $handle = fopen($tmp_name, "r");
                $row = 0;
                $imported = 0;
                $errors = 0;

                $sql = "INSERT INTO estudiantes (
                            cedula, nombre, apellido, genero, fecha_nacimiento, 
                            nivel_academico, seccion, estado_civil, email, 
                            telefono, direccion, ciudad, estado
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $row++;
                    if ($row === 1) continue; // Saltar la fila de encabezados

                    $cedula = trim($data[0] ?? '');
                    if (empty($cedula)) continue;

                    // Verificar si la cédula ya existe
                    $check = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ?");
                    $check->execute([$cedula]);
                    if ($check->fetch()) {
                        $errors++;
                        continue; // Saltar duplicados
                    }

                    try {
                        $stmt->execute([
                            $cedula,
                            trim($data[1] ?? ''), // nombre
                            trim($data[2] ?? ''), // apellido
                            trim($data[3] ?? ''), // genero
                            !empty(trim($data[4] ?? '')) ? trim($data[4]) : null, // fecha_nacimiento
                            trim($data[5] ?? ''), // nivel_academico
                            trim($data[6] ?? ''), // seccion
                            trim($data[7] ?? ''), // estado_civil
                            trim($data[8] ?? ''), // email
                            trim($data[9] ?? ''), // telefono
                            trim($data[10] ?? ''), // direccion
                            trim($data[11] ?? ''), // ciudad
                            trim($data[12] ?? 'Activo') // estado
                        ]);
                        $imported++;
                    } catch(PDOException $e) {
                        $errors++;
                    }
                }
                fclose($handle);
                $_SESSION['success_msg'] = "Importación exitosa: $imported estudiantes registrados. $errors omitidos (duplicados o errores).";
            } else {
                $_SESSION['error_msg'] = "Formato inválido. Por favor, asegúrate de guardar tu archivo Excel como .CSV antes de subirlo.";
            }
        } else {
            $_SESSION['error_msg'] = "Error al subir el archivo o sin conexión a la base de datos.";
        }
        header('Location: estudiantes.php');
        exit();
    }

    // --- GUARDAR / EDITAR MANUALMENTE ---
    if (isset($_POST['save_student'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $genero = $_POST['genero'] ?? '';
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $nivel_academico = $_POST['nivel_academico'] ?? '';
        $seccion = $_POST['seccion'] ?? '';
        $estado_civil = $_POST['estado_civil'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $estado = $_POST['estado'] ?? 'Activo';

        // Validaciones básicas
        if (empty($cedula) || empty($nombre) || empty($apellido)) {
            $_SESSION['error_msg'] = "La cédula, el nombre y el apellido son campos obligatorios.";
            header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
            exit();
        }

        if ($db_connected) {
            try {
                // Verificar si la cédula ya existe para otro estudiante
                $check_stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE cedula = ? AND id != ?");
                $check_stmt->execute([$cedula, $id]);
                if ($check_stmt->fetch()) {
                    $_SESSION['error_msg'] = "Error: Ya existe un estudiante registrado con la cédula $cedula.";
                    header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
                    exit();
                }

                if ($id > 0) {
                    $sql = "UPDATE estudiantes SET 
                                cedula = ?, nombre = ?, apellido = ?, genero = ?, fecha_nacimiento = ?, 
                                nivel_academico = ?, seccion = ?, estado_civil = ?, email = ?, 
                                telefono = ?, direccion = ?, ciudad = ?, estado = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $cedula, $nombre, $apellido, $genero, $fecha_nacimiento,
                        $nivel_academico, $seccion, $estado_civil, $email,
                        $telefono, $direccion, $ciudad, $estado, $id
                    ]);
                    $_SESSION['success_msg'] = "Estudiante actualizado correctamente.";
                } else {
                    $sql = "INSERT INTO estudiantes (
                                cedula, nombre, apellido, genero, fecha_nacimiento, 
                                nivel_academico, seccion, estado_civil, email, 
                                telefono, direccion, ciudad, estado
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $cedula, $nombre, $apellido, $genero, $fecha_nacimiento,
                        $nivel_academico, $seccion, $estado_civil, $email,
                        $telefono, $direccion, $ciudad, $estado
                    ]);
                    $_SESSION['success_msg'] = "Nuevo estudiante registrado con éxito.";
                }
                header('Location: estudiantes.php');
                exit();
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "Error al procesar los datos: " . $e->getMessage();
                header("Location: estudiantes.php?action=" . ($id > 0 ? "edit&id=$id" : "new"));
                exit();
            }
        } else {
            $_SESSION['error_msg'] = "No hay conexión activa con la base de datos para guardar la información.";
            header('Location: estudiantes.php');
            exit();
        }
    }

    // --- PROCESAR ELIMINACIÓN ---
    if (isset($_POST['delete_student_confirmed'])) {
        $id = intval($_POST['delete_id'] ?? 0);
        if ($db_connected && $id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM estudiantes WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_msg'] = "Registro de estudiante eliminado exitosamente.";
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = "No se pudo eliminar el estudiante. Posibles datos vinculados.";
            }
        } else {
            $_SESSION['error_msg'] = "Error al conectar con la base de datos o identificador de estudiante no válido.";
        }
        header('Location: estudiantes.php');
        exit();
    }
}

// OBTENER DATOS DE ESTUDIANTE PARA EDICIÓN
$current_student = null;
if ($action === 'edit' && $student_id > 0) {
    if ($db_connected) {
        $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->execute([$student_id]);
        $current_student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current_student) {
            $_SESSION['error_msg'] = "Estudiante no encontrado en el sistema.";
            header('Location: estudiantes.php');
            exit();
        }
    }
}

// OBTENER TODOS LOS ESTUDIANTES PARA LA LISTA
$estudiantes = [];
$all_grados = [];

if ($db_connected) {
    try {
        $query = "SELECT * FROM estudiantes ORDER BY nombre ASC, apellido ASC";
        $stmt = $pdo->query($query);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_con = "Fallo de consulta: " . $e->getMessage();
    }
}

// Obtener grados únicos para los filtros de búsqueda
foreach ($estudiantes as $est) {
    if (!empty($est['nivel_academico'])) {
        $all_grados[] = $est['nivel_academico'];
    }
}
$all_grados = array_unique($all_grados);
sort($all_grados);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - Liceo Admin</title>
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
        
        .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    <!-- Sidebar unificado -->
    <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
        <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30 text-white shrink-0">
                    <i class="fa-solid fa-graduation-cap text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-sm font-bold leading-tight uppercase tracking-wide text-slate-300">Liceo Admin</h1>
                    <p class="text-xs text-blue-400 font-medium">Panel Principal</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2">Módulos</div>
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-house w-5 text-slate-400"></i> Inicio
            </a>
            
            <a href="estudiantes.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-md shadow-blue-600/20 transition-colors">
                <i class="fa-solid fa-user-graduate w-5"></i> Estudiantes
            </a>

            <a href="asistencias.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                <i class="fa-solid fa-qrcode w-5 text-slate-400"></i> Asistencias
            </a>
            
            <a href="profesor.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-chalkboard-user w-5 text-slate-400"></i> Docentes
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol) ?>&background=2563eb&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol); ?></p>
                    <p class="text-xs text-slate-400">Sesión Activa</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Fondo oscuro para móvil -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-blue-600 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Módulo de Estudiantes</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Gestión de expedientes y registros académicos</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-blue-600"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Toast de notificaciones de éxito o error -->
                <?php if (!empty($success_msg)): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm slide-up mb-6">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                        <span class="font-medium text-sm"><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm slide-up mb-6">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                        <span class="font-medium text-sm"><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <!-- VISTA: DIRECTORIO / LISTADO DE ESTUDIANTES -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                                🎓 Directorio de Estudiantes
                            </h1>
                            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                                Base de Datos Activa: 
                                <span class="font-semibold text-slate-700"><?php echo $db_connected ? "Conectado (MySQL)" : "Simulación / Desconectado"; ?></span>
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3">
                            <button onclick="showImportModal()" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-file-excel"></i> Importar Datos
                            </button>
                            <a href="estudiantes.php?action=new" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-blue-600/20 flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-plus"></i> Registrar Estudiante
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row gap-4">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="searchInput" placeholder="Buscar por nombre, apellido, cédula..." onkeyup="filterTable()" class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-white shadow-sm text-sm transition-all">
                            </div>
                            
                            <select id="gradoFilter" onchange="filterTable()" class="px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 bg-white shadow-sm text-sm text-slate-700 w-full sm:w-64 appearance-none transition-all">
                                <option value="all">Todos los Niveles</option>
                                <?php foreach($all_grados as $grado): ?>
                                    <option value="<?php echo htmlspecialchars($grado); ?>"><?php echo htmlspecialchars($grado); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap" id="studentsTable">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                    <tr>
                                        <th class="px-6 py-4">Estudiante</th>
                                        <th class="px-6 py-4">Cédula</th>
                                        <th class="px-6 py-4">Nivel / Sección</th>
                                        <th class="px-6 py-4">Género</th>
                                        <th class="px-6 py-4">Contacto</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php foreach ($estudiantes as $est): ?>
                                        <tr data-grado="<?php echo htmlspecialchars($est['nivel_academico'] ?? ''); ?>" class="hover:bg-slate-50/80 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm border border-blue-100 group-hover:bg-blue-100 transition-colors">
                                                        <?php 
                                                            $inicialNombre = mb_substr($est['nombre'] ?? 'E', 0, 1, 'UTF-8');
                                                            $inicialApellido = mb_substr($est['apellido'] ?? 'S', 0, 1, 'UTF-8');
                                                            echo htmlspecialchars($inicialNombre . $inicialApellido); 
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <strong class="font-bold text-slate-800 block"><?php echo htmlspecialchars(($est['nombre'] ?? '') . ' ' . ($est['apellido'] ?? '')); ?></strong>
                                                        <span class="text-xs text-slate-500"><?php echo htmlspecialchars($est['ciudad'] ?? 'Sin ciudad'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-medium text-slate-600">
                                                <?php echo htmlspecialchars($est['cedula'] ?? 'S/C'); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                                    <?php echo htmlspecialchars(($est['nivel_academico'] ?? 'N/A') . ' - ' . ($est['seccion'] ?? 'A')); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500">
                                                <?php echo htmlspecialchars($est['genero'] ?? 'No especifica'); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-slate-700 font-medium text-sm"><?php echo htmlspecialchars(!empty($est['email']) ? $est['email'] : 'Sin correo'); ?></span>
                                                    <span class="text-xs text-slate-400"><?php echo htmlspecialchars(!empty($est['telefono']) ? $est['telefono'] : 'Sin teléfono'); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if(strcasecmp(($est['estado'] ?? ''), 'Activo') === 0): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-sm"></div> Activo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-sm"></div> Inactivo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="inline-flex gap-2">
                                                    <a href="estudiantes.php?action=edit&id=<?php echo urlencode($est['id'] ?? ''); ?>" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 border border-transparent transition-all" title="Editar Estudiante">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 border border-transparent transition-all" title="Eliminar Estudiante" onclick="showDeleteModal(<?php echo $est['id']; ?>, '<?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido'], ENT_QUOTES); ?>')">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(empty($estudiantes)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center text-slate-400">
                                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                                        <i class="fa-solid fa-user-xmark text-3xl text-slate-300"></i>
                                                    </div>
                                                    <h3 class="text-lg font-medium text-slate-600">Sin registros</h3>
                                                    <p class="text-sm mt-1">No hay estudiantes en el directorio.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($action === 'new' || $action === 'edit'): ?>
                    <!-- VISTA: CREAR / EDITAR REGISTRO -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">
                                <?php echo $action === 'edit' ? '📝 Editar Estudiante' : '👤 Registrar Nuevo Estudiante'; ?>
                            </h1>
                            <p class="text-sm text-slate-500 mt-1">Completa los campos del expediente académico del estudiante.</p>
                        </div>
                        <a href="estudiantes.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                            <i class="fa-solid fa-arrow-left"></i> Volver al listado
                        </a>
                    </div>

                    <form method="POST" action="estudiantes.php" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 slide-up">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($current_student['id'] ?? '0'); ?>">
                        
                        <!-- SECCIÓN 1: DATOS PERSONALES -->
                        <div class="text-lg font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-id-card"></i>
                            </div> 
                            Datos Personales Básicos
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                            <div>
                                <label for="cedula" class="block text-sm font-semibold text-slate-700 mb-1.5">Cédula de Identidad *</label>
                                <input type="text" id="cedula" name="cedula" placeholder="Ej: V-28123456" value="<?php echo htmlspecialchars($current_student['cedula'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                            <div>
                                <label for="nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombres *</label>
                                <input type="text" id="nombre" name="nombre" placeholder="Ingresa nombres" value="<?php echo htmlspecialchars($current_student['nombre'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                            <div>
                                <label for="apellido" class="block text-sm font-semibold text-slate-700 mb-1.5">Apellidos *</label>
                                <input type="text" id="apellido" name="apellido" placeholder="Ingresa apellidos" value="<?php echo htmlspecialchars($current_student['apellido'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                            <div>
                                <label for="genero" class="block text-sm font-semibold text-slate-700 mb-1.5">Género</label>
                                <select id="genero" name="genero" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                    <option value="" disabled <?php echo !isset($current_student['genero']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="Masculino" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="Femenino" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                                    <option value="Otro" <?php echo (isset($current_student['genero']) && $current_student['genero'] === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                            <div>
                                <label for="fecha_nacimiento" class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de Nacimiento</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($current_student['fecha_nacimiento'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                            <div>
                                <label for="estado_civil" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado Civil</label>
                                <select id="estado_civil" name="estado_civil" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                    <option value="" disabled <?php echo !isset($current_student['estado_civil']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="Soltero/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Soltero/a') ? 'selected' : ''; ?>>Soltero/a</option>
                                    <option value="Casado/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Casado/a') ? 'selected' : ''; ?>>Casado/a</option>
                                    <option value="Divorciado/a" <?php echo (isset($current_student['estado_civil']) && $current_student['estado_civil'] === 'Divorciado/a') ? 'selected' : ''; ?>>Divorciado/a</option>
                                </select>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: INFORMACIÓN ACADÉMICA -->
                        <div class="text-lg font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2 mt-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-book-open-reader"></i>
                            </div> 
                            Información Académica
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                            <div>
                                <label for="nivel_academico" class="block text-sm font-semibold text-slate-700 mb-1.5">Nivel Académico (Grado)</label>
                                <select id="nivel_academico" name="nivel_academico" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                    <option value="" disabled <?php echo !isset($current_student['nivel_academico']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="1er Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '1er Año') ? 'selected' : ''; ?>>1er Año</option>
                                    <option value="2do Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '2do Año') ? 'selected' : ''; ?>>2do Año</option>
                                    <option value="3er Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '3er Año') ? 'selected' : ''; ?>>3er Año</option>
                                    <option value="4to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '4to Año') ? 'selected' : ''; ?>>4to Año</option>
                                    <option value="5to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '5to Año') ? 'selected' : ''; ?>>5to Año</option>
                                    <option value="6to Año" <?php echo (isset($current_student['nivel_academico']) && $current_student['nivel_academico'] === '6to Año') ? 'selected' : ''; ?>>6to Año</option>
                                </select>
                            </div>
                            <div>
                                <label for="seccion" class="block text-sm font-semibold text-slate-700 mb-1.5">Sección</label>
                                <select id="seccion" name="seccion" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                    <option value="" disabled <?php echo !isset($current_student['seccion']) ? 'selected' : ''; ?>>Selecciona...</option>
                                    <option value="A" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'A') ? 'selected' : ''; ?>>Sección A</option>
                                    <option value="B" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'B') ? 'selected' : ''; ?>>Sección B</option>
                                    <option value="C" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'C') ? 'selected' : ''; ?>>Sección C</option>
                                    <option value="D" <?php echo (isset($current_student['seccion']) && $current_student['seccion'] === 'D') ? 'selected' : ''; ?>>Sección D</option>
                                </select>
                            </div>
                            <div>
                                <label for="estado" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado de Matrícula</label>
                                <select id="estado" name="estado" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                                    <option value="Activo" <?php echo (isset($current_student['estado']) && $current_student['estado'] === 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo (isset($current_student['estado']) && $current_student['estado'] === 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: DATOS DE CONTACTO Y DIRECCIÓN -->
                        <div class="text-lg font-bold text-slate-800 mb-5 pb-3 border-b border-slate-100 flex items-center gap-2 mt-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div> 
                            Datos de Ubicación y Contacto
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-5">
                            <div>
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo Electrónico</label>
                                <input type="email" id="email" name="email" placeholder="ejemplo@liceo.edu" value="<?php echo htmlspecialchars($current_student['email'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                            <div>
                                <label for="telefono" class="block text-sm font-semibold text-slate-700 mb-1.5">Número de Teléfono</label>
                                <input type="text" id="telefono" name="telefono" placeholder="Ej: 0412-3456789" value="<?php echo htmlspecialchars($current_student['telefono'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                            <div>
                                <label for="ciudad" class="block text-sm font-semibold text-slate-700 mb-1.5">Ciudad</label>
                                <input type="text" id="ciudad" name="ciudad" placeholder="Ej: Valera" value="<?php echo htmlspecialchars($current_student['ciudad'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label for="direccion" class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección Domiciliaria Completa</label>
                            <textarea id="direccion" name="direccion" placeholder="Sector, Avenida, número de casa, punto de referencia..." class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-slate-50 focus:bg-white transition-all shadow-sm resize-none min-h-[100px]"><?php echo htmlspecialchars($current_student['direccion'] ?? ''); ?></textarea>
                        </div>

                        <!-- ACCIONES DEL FORMULARIO -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-5 border-t border-slate-100">
                            <a href="estudiantes.php" class="text-center px-6 py-3 rounded-xl font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Cancelar</a>
                            <button type="submit" name="save_student" class="px-6 py-3 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Expediente
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <footer class="mt-12 border-t border-slate-200 pt-6 text-center text-sm text-slate-400 pb-8">
                <p>&copy; <?= date('Y'); ?> Liceo Admin. Diseñado para la excelencia.</p>
            </footer>
        </div>
    </main>

    <!-- MODAL DE IMPORTACIÓN EXCEL/CSV -->
    <div id="importModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    Importar Estudiantes
                </h2>
                <button onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="p-6 bg-white">
                <p class="text-sm text-slate-600 mb-4">
                    Para registrar múltiples estudiantes automáticamente, utiliza un archivo de hoja de cálculo guardado en formato <strong>CSV (delimitado por comas)</strong>.
                </p>
                
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                    <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wide mb-2"><i class="fa-solid fa-circle-info mr-1"></i> Orden de las Columnas Requerido:</h4>
                    <p class="text-xs text-blue-700 leading-relaxed font-mono bg-white p-2 rounded border border-blue-100">
                        1. Cédula | 2. Nombre | 3. Apellido | 4. Género | 5. Fecha Nac. (YYYY-MM-DD) | 6. Nivel | 7. Sección | 8. Est. Civil | 9. Email | 10. Teléfono | 11. Dirección | 12. Ciudad | 13. Estado (Activo/Inactivo)
                    </p>
                    <p class="text-xs text-blue-600 mt-2 italic">* La primera fila del archivo será ignorada (se asume que son los títulos de las columnas).</p>
                </div>

                <form method="POST" action="estudiantes.php" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="file_import" class="block text-sm font-semibold text-slate-700 mb-2">Seleccionar Archivo (.csv)</label>
                        <input type="file" name="file_import" id="file_import" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all border border-slate-200 rounded-xl bg-slate-50 cursor-pointer">
                    </div>
                    
                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4 mt-2">
                        <button type="button" onclick="closeImportModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors text-sm text-center">
                            Cancelar
                        </button>
                        <button type="submit" name="import_students" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-emerald-600/20 text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Procesar Archivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden glass-modal border border-white/20">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-white">
                <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-red-100 text-red-500 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    ¿Eliminar Expediente?
                </h2>
                <button onclick="closeDeleteModal()" class="text-slate-400 hover:text-red-500 hover:bg-red-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="p-6 bg-white">
                <p class="text-sm text-slate-600 mb-6">
                    ¿Está seguro de que desea eliminar permanentemente al estudiante <strong id="deleteStudentName" class="text-slate-800"></strong> del sistema? Esta acción es irreversible y podría remover calificaciones o datos asociados.
                </p>
                <form method="POST" action="estudiantes.php" class="flex flex-col sm:flex-row justify-end gap-3">
                    <input type="hidden" name="delete_id" id="deleteStudentId" value="0">
                    <button type="button" onclick="closeDeleteModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors text-sm text-center">
                        Cancelar
                    </button>
                    <button type="submit" name="delete_student_confirmed" class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white font-semibold py-2.5 px-5 rounded-xl transition-colors shadow-md shadow-red-500/20 text-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash-can"></i> Eliminar Registro
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Filtrar tabla dinámicamente según la entrada y el combo de selección
        function filterTable() {
            const input = document.getElementById("searchInput").value.toUpperCase();
            const filterGrado = document.getElementById("gradoFilter").value;
            const table = document.getElementById("studentsTable");
            if (!table) return;
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                if(!row.hasAttribute("data-grado")) continue; 

                const rowGrado = row.getAttribute("data-grado") || "";
                const textContent = row.textContent || row.innerText;
                
                const matchesSearch = textContent.toUpperCase().indexOf(input) > -1;
                const matchesGrado = (filterGrado === "all" || rowGrado === filterGrado);

                if (matchesSearch && matchesGrado) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        }

        // Mostrar/Ocultar Modal de Importación
        function showImportModal() {
            const modal = document.getElementById("importModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeImportModal() {
            const modal = document.getElementById("importModal");
            modal.classList.add("hidden");
            setTimeout(() => { modal.classList.remove("flex"); }, 50); 
        }

        // Mostrar Modal de confirmación de eliminación
        function showDeleteModal(id, studentName) {
            document.getElementById("deleteStudentId").value = id;
            document.getElementById("deleteStudentName").innerText = studentName;
            
            const modal = document.getElementById("deleteConfirmModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        // Ocultar Modal de eliminación
        function closeDeleteModal() {
            const modal = document.getElementById("deleteConfirmModal");
            modal.classList.add("hidden");
            setTimeout(() => { modal.classList.remove("flex"); }, 50);
        }

        // Sidebar Móvil
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
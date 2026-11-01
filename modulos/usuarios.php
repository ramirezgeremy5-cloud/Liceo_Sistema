<?php
session_start();
require_once '../config/database.php'; 


if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado'] || $_SESSION['rol'] !== 'Administrador') {
    // header('Location: ../login.php');
    // exit;
}

$mensaje = '';
$rol_actual = $_SESSION['rol'] ?? 'Administrador';
$nombre_actual = $_SESSION['nombre'] ?? 'Administrador';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Gestión de Usuarios";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (cedula, username, password, nombre, apellido, email, rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([
            $_POST['cedula'], $_POST['username'], $hash, $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado']
        ]);
        $mensaje = "Usuario creado exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al crear: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET cedula=?, username=?, password=?, nombre=?, apellido=?, email=?, rol=?, estado=? WHERE id=?");
        $parametros = [
            $_POST['cedula'], $_POST['username'], $hash, $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado'], $_POST['id']
        ];
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET cedula=?, username=?, nombre=?, apellido=?, email=?, rol=?, estado=? WHERE id=?");
        $parametros = [
            $_POST['cedula'], $_POST['username'], $_POST['nombre'], 
            $_POST['apellido'], $_POST['email'], $_POST['rol'], $_POST['estado'], $_POST['id']
        ];
    }
    try {
        $stmt->execute($parametros);
        $mensaje = "Usuario actualizado exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
    }
}

if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar' && isset($_POST['id_usuario'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    try {
        $stmt->execute([$_POST['id_usuario']]);
        $mensaje = "Usuario eliminado.";
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
            <div class="p-5 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
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
                <a href="labor-social.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors"><i class="fa-solid fa-handshake-angle w-5 text-slate-400"></i> Labor Social</a>
                <a href="usuarios.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md transition-colors"><i class="fa-solid fa-users w-5"></i> Usuarios</a>
            </nav>
        </aside>

        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 text-xl p-2 rounded-lg hover:bg-slate-100"><i class="fa-solid fa-bars"></i></button>
                    <div><h2 class="text-xl lg:text-2xl font-bold text-slate-800">Módulo de Usuarios</h2></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-slate-600 hidden sm:inline"><?= htmlspecialchars($nombre_actual); ?></span>
                    <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold shadow-sm"><?= strtoupper(substr($nombre_actual, 0, 1)); ?></div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
                <div class="max-w-6xl mx-auto space-y-6">

                    
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-700/50 relative overflow-hidden">
                        <div class="space-y-2 text-center sm:text-left z-10">
                            <span class="inline-block bg-amber-500/20 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-500/30">Control de Acceso</span>
                            <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Gestión de Cuentas y Credenciales</h2>
                            <p class="text-slate-300 text-sm max-w-xl">Administración general de usuarios del sistema, asignación de roles y estatus de operatividad institucional.</p>
                        </div>
                        <div class="banner-logo-center z-10">
                            <?= $escudo_svg; ?>
                        </div>
                    </div>

                    <?php if ($mensaje): ?>
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3 slide-up">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                            <span class="font-medium text-sm"><?= htmlspecialchars($mensaje); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden slide-up">
                        <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Lista de Usuarios del Sistema</h3>
                                <p class="text-sm text-slate-500">Administra cuentas, roles y estados de acceso.</p>
                            </div>
                            <button onclick="abrirModal('crear')" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-medium py-2.5 px-5 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-xs font-semibold">
                                    <tr>
                                        <th class="px-6 py-4">Cédula</th>
                                        <th class="px-6 py-4">Usuario</th>
                                        <th class="px-6 py-4">Nombre y Apellido</th>
                                        <th class="px-6 py-4">Rol</th>
                                        <th class="px-6 py-4 text-center">Estado</th>
                                        <th class="px-6 py-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php foreach ($usuarios as $u): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700"><?= htmlspecialchars($u['cedula']); ?></td>
                                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($u['username']); ?></td>
                                        <td class="px-6 py-4 font-bold text-slate-800"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                                <?= htmlspecialchars($u['rol']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold <?= $u['estado'] === 'Activo' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                                <?= htmlspecialchars($u['estado']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button onclick='abrirModal("editar", <?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>)' class="bg-slate-100 text-slate-700 hover:bg-blue-600 hover:text-white border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5">
                                                    <i class="fa-solid fa-pen"></i> Editar
                                                </button>
                                                <button onclick='abrirModalEliminar(<?= $u['id']; ?>, "<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8'); ?>")' class="bg-slate-100 text-slate-700 hover:bg-rose-600 hover:text-white border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5">
                                                    <i class="fa-solid fa-trash"></i> Borrar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div id="usuarioModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden glass-modal">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 id="modalTitle" class="text-xl font-bold text-slate-800">Nuevo Usuario</h3>
                    <p class="text-xs text-slate-500">Completa los datos del usuario.</p>
                </div>
                <button type="button" onclick="cerrarModal()" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            
            <form method="POST" id="usuarioForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="userId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Cédula</label>
                        <input type="text" name="cedula" id="cedula" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Usuario (Username)</label>
                        <input type="text" name="username" id="username" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500">
                    <p id="passHelp" class="text-xs text-slate-400 mt-1 hidden">Déjalo en blanco si no deseas cambiarla.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Apellido</label>
                        <input type="text" name="apellido" id="apellido" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Rol</label>
                        <select name="rol" id="rol" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                            <option value="Administrador">Administrador</option>
                            <option value="secretaria_direccion">Secretaria Dirección</option>
                            <option value="coordinador_academico">Coordinador Académico</option>
                            <option value="profesor">Profesor</option>
                            <option value="practicas_profesionales">Prácticas Profesionales</option>
                            <option value="labor_social">Labor Social</option>
                            <option value="estudiante">Estudiante</option>
                            <option value="representante">Representante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                        <select name="estado" id="estado" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:border-amber-500" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3.5 rounded-xl mt-4 transition-colors shadow-md">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <div id="modalEliminar" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md slide-up overflow-hidden text-center p-8 border-t-4 border-rose-500">
            <div class="w-20 h-20 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Eliminar Usuario</h3>
            <p class="text-sm text-slate-600 mb-6">¿Estás seguro que deseas eliminar permanentemente al usuario <strong id="delete_nombre_usuario" class="text-slate-800"></strong>?</p>
            
            <form method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_usuario" id="delete_id_usuario">
                <div class="flex gap-3">
                    <button type="button" onclick="cerrarModalEliminar()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-rose-600 text-white rounded-xl font-semibold hover:bg-rose-700 transition-colors"><i class="fa-solid fa-trash mr-1"></i> Sí, Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('usuarioModal');
        const modalEliminar = document.getElementById('modalEliminar');
        const form = document.getElementById('usuarioForm');

        function abrirModal(tipo, datos = null) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            form.reset();
            
            if (tipo === 'crear') {
                document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
                document.getElementById('accion').value = 'crear';
                document.getElementById('password').required = true;
                document.getElementById('passHelp').classList.add('hidden');
            } else if (tipo === 'editar') {
                document.getElementById('modalTitle').innerText = 'Editar Usuario';
                document.getElementById('accion').value = 'editar';
                document.getElementById('password').required = false;
                document.getElementById('passHelp').classList.remove('hidden');
                
                document.getElementById('userId').value = datos.id;
                document.getElementById('cedula').value = datos.cedula;
                document.getElementById('username').value = datos.username;
                document.getElementById('nombre').value = datos.nombre;
                document.getElementById('apellido').value = datos.apellido;
                document.getElementById('email').value = datos.email;
                document.getElementById('rol').value = datos.rol;
                document.getElementById('estado').value = datos.estado;
            }
        }

        function cerrarModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalEliminar(id, nombre) {
            document.getElementById('delete_id_usuario').value = id;
            document.getElementById('delete_nombre_usuario').innerText = nombre;
            modalEliminar.classList.remove('hidden');
            modalEliminar.classList.add('flex');
        }

        function cerrarModalEliminar() {
            modalEliminar.classList.add('hidden');
            modalEliminar.classList.remove('flex');
        }

        window.onclick = function(event) {
            if (event.target == modal) { cerrarModal(); }
            if (event.target == modalEliminar) { cerrarModalEliminar(); }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>
<?php
session_start();

// Configuración de conexión a la base de datos
$host = 'localhost';
$dbname = 'escuela_tecnica';
$user = 'root'; 
$pass = '';     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

if (!isset($_SESSION['usuario_logueado'])) {
    $_SESSION['usuario_logueado'] = true;
    $_SESSION['rol'] = 'Administrador';
}

$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$año_actual = date("Y");

$puede_publicar = ($rol_actual === 'Administrador' || $rol_actual === 'Secretaria');
$puede_editar_eliminar = ($rol_actual === 'Administrador' || $rol_actual === 'Secretaria');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_noticia']) && $puede_publicar) {
    $categoria = htmlspecialchars($_POST['tipo'] ?? '');
    $titulo = htmlspecialchars($_POST['titulo'] ?? '');
    $descripcion = htmlspecialchars($_POST['cuerpo'] ?? '');
    $fecha_publicacion = date('Y-m-d');
    $autor = $rol_actual;
    
    $sql = "INSERT INTO noticias (titulo, descripcion, categoria, fecha_publicacion, autor, estado) VALUES (?, ?, ?, ?, ?, 'Activo')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titulo, $descripcion, $categoria, $fecha_publicacion, $autor]);
    
    header("Location: noticias.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_noticia']) && $puede_editar_eliminar) {
    $id_editar = (int)($_POST['id_noticia'] ?? 0);
    $categoria = htmlspecialchars($_POST['tipo'] ?? '');
    $titulo = htmlspecialchars($_POST['titulo'] ?? '');
    $descripcion = htmlspecialchars($_POST['cuerpo'] ?? '');
    
    $sql = "UPDATE noticias SET titulo = ?, descripcion = ?, categoria = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titulo, $descripcion, $categoria, $id_editar]);
    
    header("Location: noticias.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_noticia']) && $puede_editar_eliminar) {
    $id_eliminar = (int)($_POST['id_eliminar'] ?? 0);
    
    $sql = "DELETE FROM noticias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_eliminar]);
    
    header("Location: noticias.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM noticias ORDER BY id DESC");
$noticias_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatearFechaNoticia($fecha_db) {
    if (!$fecha_db) return '';
    $timestamp = strtotime($fecha_db);
    $meses = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio', '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
    return date('d', $timestamp) . " de " . $meses[date('m', $timestamp)] . ", " . date('Y', $timestamp);
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
    <title>Muro de Noticias - E.T. Pedro Garcia Leal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up { animation: slideUp 0.5s ease-out forwards; opacity: 0; }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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
            <a href="noticias.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all">
                <i class="fa-solid fa-bullhorn w-5"></i> Muro de Noticias
            </a>
            <a href="chatbot.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                <i class="fa-solid fa-headset w-5 text-slate-400"></i> Centro de Ayuda
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center gap-3 px-2">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($rol_actual) ?>&background=f59e0b&color=fff" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-slate-700">
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($rol_actual); ?></p>
                    <p class="text-xs text-slate-400">Sesión Activa</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 transition-colors text-xl p-2 -ml-2 rounded-lg hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-slate-800 leading-tight">Muro de Noticias</h2>
                    <p class="text-xs text-slate-500 hidden sm:block">Anuncios y comunicados oficiales de la institución</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-amber-500"></i> <span class="hidden sm:inline">Hoy: </span><?= date('d/m/Y'); ?>
                </span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">
                
                
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6 border border-slate-700/50 relative overflow-hidden">
                    <div class="space-y-2 text-center sm:text-left z-10">
                        <span class="inline-block bg-amber-500/20 text-amber-400 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-500/30">Canal Oficial</span>
                        <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Cartelera Digital Informativa</h2>
                        <p class="text-slate-300 text-sm max-w-xl">Mantente al día con los avisos, fechas de evaluaciones, jornadas institucionales y comunicados dirigidos a toda la comunidad educativa.</p>
                    </div>
                    <div class="banner-logo-center z-10">
                        <?= $escudo_svg; ?>
                    </div>
                </div>

                
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-amber-600 transition-colors bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:shadow">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                    </a>
                    
                    <?php if($puede_publicar): ?>
                    <button onclick="abrirModalNueva()" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 px-6 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-pen-to-square"></i> Publicar Comunicado
                    </button>
                    <?php endif; ?>
                </div>

                
                <div class="space-y-6 mt-4">
                    <?php if (empty($noticias_data)): ?>
                        <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-300">
                            <i class="fa-regular fa-newspaper text-5xl text-slate-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-slate-700">No hay noticias publicadas</h3>
                            <p class="text-slate-500 mt-1">Los comunicados recientes aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $delay = 0;
                        foreach($noticias_data as $noticia): 
                            
                            $badgeColor = 'bg-slate-100 text-slate-700';
                            $iconType = 'fa-circle-info';
                            
                            if ($noticia['categoria'] === 'Importante') {
                                $badgeColor = 'bg-rose-100 text-rose-700 border border-rose-200';
                                $iconType = 'fa-triangle-exclamation';
                            }
                            else if ($noticia['categoria'] === 'Información') {
                                $badgeColor = 'bg-sky-100 text-sky-700 border border-sky-200';
                                $iconType = 'fa-info';
                            }
                            else if ($noticia['categoria'] === 'Evento') {
                                $badgeColor = 'bg-amber-100 text-amber-800 border border-amber-200';
                                $iconType = 'fa-calendar-star';
                            }
                        ?>
                            <article class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 slide-up relative" style="animation-delay: <?= $delay ?>s;">
                                
                                <div class="flex justify-between items-start mb-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $badgeColor ?>">
                                        <i class="fa-solid <?= $iconType ?>"></i> <?= htmlspecialchars($noticia['categoria'] ?? ''); ?>
                                    </span>
                                    <div class="text-right">
                                        <span class="block text-sm font-semibold text-slate-800"><?= htmlspecialchars($noticia['autor'] ?? ''); ?></span>
                                        <span class="text-xs text-slate-400"><i class="fa-regular fa-clock mr-1"></i> <?= formatearFechaNoticia($noticia['fecha_publicacion']); ?></span>
                                    </div>
                                </div>
                                
                                <h2 class="text-2xl font-bold text-slate-900 mb-3 pr-20"><?= htmlspecialchars($noticia['titulo'] ?? ''); ?></h2>
                                <p class="text-slate-600 leading-relaxed text-[15px]">
                                    <?= nl2br(htmlspecialchars($noticia['descripcion'] ?? '')); ?>
                                </p>
                                
                                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                                    <button class="text-slate-400 hover:text-amber-500 text-sm font-medium flex items-center gap-1.5 transition-colors">
                                        <i class="fa-solid fa-share-nodes"></i> Compartir
                                    </button>
                                    
                                    <?php if($puede_editar_eliminar): ?>
                                    <div class="flex gap-2">
                                        <button 
                                            data-id="<?= htmlspecialchars($noticia['id'] ?? '') ?>"
                                            data-tipo="<?= htmlspecialchars($noticia['categoria'] ?? '') ?>"
                                            data-titulo="<?= htmlspecialchars($noticia['titulo'] ?? '') ?>"
                                            data-cuerpo="<?= htmlspecialchars($noticia['descripcion'] ?? '') ?>"
                                            onclick="abrirModalEditar(this)" 
                                            class="text-amber-600 hover:bg-amber-50 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                        
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este comunicado?');" class="inline">
                                            <input type="hidden" name="id_eliminar" value="<?= htmlspecialchars($noticia['id'] ?? '') ?>">
                                            <button type="submit" name="eliminar_noticia" class="text-rose-500 hover:bg-rose-50 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                                                <i class="fa-solid fa-trash-can"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php 
                            $delay += 0.1;
                        endforeach; 
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if($puede_publicar): ?>
    <div id="modalNuevaNoticia" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
                <h2 class="text-xl font-bold text-slate-800">Publicar Nuevo Comunicado</h2>
                <button onclick="cerrarModalNueva()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Categoría del Anuncio</label>
                    <select name="tipo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                        <option value="Información">Información General (Azul)</option>
                        <option value="Importante">Aviso Importante (Rojo)</option>
                        <option value="Evento">Evento Académico (Ámbar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Título de la Noticia</label>
                    <input type="text" name="titulo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required placeholder="Ej: Entrega de boletines de 3er lapso">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Cuerpo del Mensaje</label>
                    <textarea name="cuerpo" rows="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required placeholder="Redacte toda la información necesaria aquí..."></textarea>
                </div>
                <button type="submit" name="nueva_noticia" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 rounded-xl transition-colors mt-2 shadow-md">
                    Publicar en el Muro
                </button>
            </form>
        </div>
    </div>

    <div id="modalEditarNoticia" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 fade-in px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg slide-up overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
                <h2 class="text-xl font-bold text-slate-800">Editar Comunicado</h2>
                <button onclick="cerrarModalEditar()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="id_noticia" id="edit_id_noticia">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Categoría del Anuncio</label>
                    <select name="tipo" id="edit_tipo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                        <option value="Información">Información General (Azul)</option>
                        <option value="Importante">Aviso Importante (Rojo)</option>
                        <option value="Evento">Evento Académico (Ámbar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Título de la Noticia</label>
                    <input type="text" name="titulo" id="edit_titulo" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Cuerpo del Mensaje</label>
                    <textarea name="cuerpo" id="edit_cuerpo" rows="6" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/50 bg-slate-50 focus:bg-white transition-colors" required></textarea>
                </div>
                <button type="submit" name="editar_noticia" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-3 rounded-xl transition-colors mt-2 shadow-md">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>
    
    <script>
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

        function abrirModalNueva() {
            const modal = document.getElementById('modalNuevaNoticia');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function cerrarModalNueva() {
            const modal = document.getElementById('modalNuevaNoticia');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalEditar(btn) {
            document.getElementById('edit_id_noticia').value = btn.dataset.id;
            document.getElementById('edit_tipo').value = btn.dataset.tipo;
            document.getElementById('edit_titulo').value = btn.dataset.titulo;
            document.getElementById('edit_cuerpo').value = btn.dataset.cuerpo; 

            const modal = document.getElementById('modalEditarNoticia');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarNoticia');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php endif; ?>
</body>
</html>
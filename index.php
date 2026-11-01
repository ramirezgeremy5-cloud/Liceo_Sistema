<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['nombre'] ?? 'Usuario';

// Array consolidado de módulos con iconos SVG modernos
$modulos = [
    ['nombre' => 'Profesores', 'link' => 'modulos/profesor.php', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z', 'roles' => ['Administrador', 'Profesor', 'Labor Social']],
    ['nombre' => 'Gestion de secciones', 'link' => 'modulos/gestion_personas.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'coordinador_academico', 'coordinador_académico', 'Coordinador Académico', 'Coordinador']],
    ['nombre' => 'Control de Asistencias', 'link' => 'modulos/asistencias.php', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'roles' => ['Administrador', 'Profesor']],
    ['nombre' => 'Escanear Asistencia', 'link' => 'modulos/escanear-asistencia.php', 'icon' => 'M12 4v1m0 14v1m8-8h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Estudiante','Administrador']],
    ['nombre' => 'Estudiantes', 'link' => 'modulos/gestion-estudiantes.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'Secretaria', 'coordinador_academico', 'coordinador_académico', 'Coordinador Académico', 'Coordinador']],
    ['nombre' => 'Representantes', 'link' => 'modulos/representante.php', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20H2v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'roles' => ['Representante', 'Administrador']],
    ['nombre' => 'Coordinación', 'link' => 'modulos/coordinacion.php', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'roles' => ['coordinador_academico', 'coordinador_académico', 'Coordinador Académico', 'Coordinador', 'Administrador']],
    ['nombre' => 'Pasantías', 'link' => 'modulos/pasantias.php', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'roles' => ['Administrador','Prácticas Profesionales']],
    ['nombre' => 'Labor Social', 'link' => 'modulos/labor-social.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'Labor Social']],
    ['nombre' => 'Mi Informacion.', 'link' => 'inicio-estudiante.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Estudiante']],
    ['nombre' => 'Noticias', 'link' => 'modulos/noticias.php', 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', 'roles' => 'todos'],
    ['nombre' => 'Guia Informativa', 'link' => 'modulos/chatbot.php', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'roles' => 'todos'],
    ['nombre' => 'Planillas', 'link' => 'modulos/generar-planilla.php', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'roles' => ['Administrador', 'Secretaria']],
    ['nombre' => 'Usuarios', 'link' => 'modulos/usuarios.php', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['Administrador', 'coordinador_academico', 'coordinador_académico', 'Coordinador Académico', 'Coordinador']]
];


function usuario_tiene_permiso($rol_usuario, $roles_permitidos) {
    if ($roles_permitidos === 'todos') {
        return true;
    }
    if (!is_array($roles_permitidos)) {
        $roles_permitidos = [$roles_permitidos];
    }
    
    $rol_usuario_norm = mb_strtolower(trim($rol_usuario));
    
    foreach ($roles_permitidos as $r) {
        $r_norm = mb_strtolower(trim($r));
        if ($rol_usuario_norm === $r_norm) {
            return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Informativa - E.T. Pedro Garcia Leal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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

        .navbar { 
            background: rgba(15, 23, 42, 0.96); 
            backdrop-filter: blur(10px);
            color: white; 
            padding: 0.85rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .brand-container { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
        }

        .brand-logo { 
            width: 40px; 
            height: 46px; 
            flex-shrink: 0;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            filter: drop-shadow(0 2px 6px rgba(245, 158, 11, 0.4));
            transition: transform 0.3s ease;
        }
        .brand-logo:hover { transform: scale(1.08); }
        .brand-logo svg { width: 100%; height: 100%; display: block; }

        .navbar-brand { display: flex; flex-direction: column; }
        .navbar-brand h1 { margin: 0; font-size: 1.15rem; font-weight: 700; letter-spacing: 0.5px; }
        .navbar-brand span { font-size: 0.78rem; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;}
        
        .user-profile { display: flex; align-items: center; gap: 14px; }
        .avatar { 
            width: 40px; height: 40px; border-radius: 50%; background: var(--accent); 
            display: flex; align-items: center; justify-content: center; font-weight: bold; 
            font-size: 1.2rem; color: white; flex-shrink: 0; box-shadow: 0 2px 8px rgba(245,158,11,0.3);
        }
        .user-info p { margin: 0; font-size: 0.9rem; color: #ffffff; }
        .user-info .role { font-size: 0.75rem; color: var(--accent); font-weight: 600; text-transform: uppercase; }
        
        .btn-logout { 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; 
            padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; 
            transition: all 0.2s; white-space: nowrap; font-weight: 600;
        }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.2); border-color: #ef4444; color: #fca5a5; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 20px;
            padding: 30px 40px;
            color: white;
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.2) 0%, rgba(0,0,0,0) 70%);
            pointer-events: none;
        }

        .banner-text h2 { font-size: 1.75rem; font-weight: 800; margin: 0 0 8px 0; color: #ffffff; }
        .banner-text p { font-size: 0.95rem; color: #94a3b8; margin: 0 0 16px 0; max-width: 600px; line-height: 1.5; }
        
        .banner-badge {
            display: inline-block; background: rgba(245, 158, 11, 0.15); color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.75rem; font-weight: 700;
            padding: 5px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;
        }

        .banner-logo {
            width: 90px;
            height: 105px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.5));
        }
        .banner-logo svg { width: 100%; height: 100%; display: block; }

        .section-title { 
            font-size: 1.25rem; font-weight: 700; color: var(--primary); 
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px; 
        }

        .modules-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
            gap: 24px; 
        }
        
        .module-card { 
            background: var(--surface); 
            border-radius: 16px; 
            padding: 24px; 
            text-decoration: none; 
            color: var(--text-main); 
            display: flex; 
            flex-direction: column; 
            align-items: flex-start;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
            background: var(--accent); transform: scaleY(0); transition: transform 0.3s ease;
            transform-origin: bottom;
        }
        
        .module-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); 
            border-color: transparent;
        }
        
        .module-card:hover::before { transform: scaleY(1); transform-origin: top; }
        .module-card:hover .icon-wrapper { background: var(--primary); color: white; }

        .icon-wrapper { 
            width: 56px; height: 56px; border-radius: 12px; background: #f1f5f9; 
            color: var(--primary); display: flex; align-items: center; justify-content: center; 
            margin-bottom: 16px; transition: all 0.3s ease;
        }
        .icon-wrapper svg { width: 28px; height: 28px; }
        
        .module-card h3 { margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 600; color: var(--primary); }
        .module-card p { margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; }
        
        .arrow-icon { position: absolute; bottom: 24px; right: 24px; color: #cbd5e1; transition: all 0.3s; opacity: 0; transform: translateX(-10px);}
        .module-card:hover .arrow-icon { opacity: 1; transform: translateX(0); color: var(--accent); }

        @media (max-width: 768px) {
            .navbar { flex-direction: column; align-items: stretch; padding: 1rem; gap: 12px; }
            .user-profile { justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 10px; }
            .welcome-banner { flex-direction: column-reverse; text-align: center; padding: 24px 20px; gap: 20px; }
            .banner-text p { max-width: 100%; }
            .banner-logo { width: 75px; height: 90px; }
            .container { margin: 15px auto; padding: 0 12px; }
            .modules-grid { grid-template-columns: 1fr; gap: 16px; }
            .arrow-icon { opacity: 1; transform: translateX(0); color: var(--accent); }
        }
    </style>
</head>
<body>

    <?php 
    $escudo_svg = '
    <svg viewBox="0 0 200 240" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 10L180 35V110C180 165 140 210 100 230C60 210 20 165 20 110V35L100 10Z" fill="#facc15" stroke="#ca8a04" stroke-width="5"/>
        <path d="M26 40L100 18L174 40V105C174 155 138 196 100 214C62 196 26 155 26 105V40Z" fill="#f8fafc"/>
        <path d="M26 95L174 125V175C174 175 140 210 100 220C60 210 26 175 26 175V95Z" fill="#7f1d1d"/>
        <text x="100" y="52" font-size="10" font-family="Arial, sans-serif" font-weight="bold" fill="#0f172a" text-anchor="middle">INFORMÁTICA, COMERCIO</text>
        <text x="100" y="66" font-size="10" font-family="Arial, sans-serif" font-weight="bold" fill="#0f172a" text-anchor="middle">Y SERVICIOS ADMINISTRATIVOS</text>
        <rect x="45" y="80" width="45" height="40" rx="4" fill="#7f1d1d" stroke="#facc15" stroke-width="2"/>
        <rect x="53" y="88" width="29" height="24" rx="2" fill="none" stroke="#f8fafc" stroke-width="2"/>
        <rect x="61" y="96" width="13" height="8" fill="#f8fafc"/>
        <g transform="rotate(-6 100 150)">
            <rect x="35" y="135" width="130" height="26" fill="#facc15" rx="3"/>
            <text x="100" y="152" font-size="11" font-family="Arial, sans-serif" font-weight="bold" fill="#7f1d1d" text-anchor="middle">E.T.C.R.Z. "PEDRO GARCÍA LEAL"</text>
        </g>
        <g transform="translate(85, 175)">
            <path d="M0 10C10 5 20 10 30 5V25C20 30 10 25 0 30V10Z" fill="#ffffff"/>
            <path d="M30 5C40 10 50 5 60 10V30C50 25 40 30 30 25V5Z" fill="#e2e8f0"/>
            <text x="30" y="14" font-size="5" font-weight="bold" fill="#7f1d1d" text-anchor="middle">FUNDADO EL</text>
            <text x="30" y="21" font-size="5" font-weight="bold" fill="#7f1d1d" text-anchor="middle">01-10-1975</text>
        </g>
        <ellipse cx="100" cy="218" rx="36" ry="10" fill="#7f1d1d"/>
        <text x="100" y="222" font-size="8" font-family="Arial, sans-serif" font-weight="bold" fill="#facc15" text-anchor="middle">VALERA EDO TRUJILLO</text>
    </svg>';
    ?>

    <nav class="navbar">
        <div class="brand-container">
            <div class="brand-logo">
                <?= $escudo_svg; ?>
            </div>
            <div class="navbar-brand">
                <h1>Sistema de Gestión Informativa</h1>
                <span>E.T. Pedro Garcia Leal</span>
            </div>
        </div>
        <div class="user-profile">
            <div class="user-info" style="text-align: right;">
                <p><strong><?php echo htmlspecialchars($nombre); ?></strong></p>
                <p class="role"><?php echo htmlspecialchars($rol); ?></p>
            </div>
            <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 1)); ?></div>
            <a href="cerrar-sesion.php" class="btn-logout">Salir</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <div class="banner-text">
                <span class="banner-badge">Panel General</span>
                <h2>Bienvenido, <?php echo htmlspecialchars($nombre); ?></h2>
                <p>Seleccione el módulo al que desea acceder para gestionar las actividades académicas y administrativas de la institución.</p>
            </div>
            <div class="banner-logo">
                <?= $escudo_svg; ?>
            </div>
        </div>

        <div class="section-title">
            <span>Módulos del Sistema</span>
        </div>

        <div class="modules-grid">
            <?php foreach ($modulos as $m): ?>
                <?php 
                    $puede_ver = usuario_tiene_permiso($rol, $m['roles']);
                    if ($puede_ver): 
                ?>
                    <a href="<?php echo $m['link']; ?>" class="module-card">
                        <div class="icon-wrapper">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $m['icon']; ?>"></path></svg>
                        </div>
                        <h3><?php echo $m['nombre']; ?></h3>
                        <p>Acceder a las herramientas y configuraciones del módulo.</p>
                        
                        <svg class="arrow-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    // Redirección si se requiere autenticación
    // header('Location: ../login.php');
    // exit;
}

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$rol_actual = $_SESSION['rol'] ?? 'Administrador';
$nombre_actual = $_SESSION['nombre'] ?? 'Administrador';
$modulo_actual = "Planilla de Inscripción";


$logo_path = "../assets/img/logo.jpg"; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modulo_actual; ?> - <?= $nombre_institucion; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        
        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            html, body {
                height: 100%;
                overflow: hidden !important;
                background: white !important;
            }
            aside, header, .no-print, #sidebarOverlay {
                display: none !important;
            }
            body, main {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            .print-wrapper {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #contenidoPDF {
                border: none !important;
                box-shadow: none !important;
                padding: 12px 20px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .page-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 bg-slate-50">

    
    <template id="escudo-svg">
        <svg viewBox="0 0 300 350" xmlns="http://www.w3.org/2000/svg" class="w-full h-full object-contain">
            <!-- Borde exterior amarillo -->
            <path d="M 150 10 L 270 50 C 270 50 280 160 270 210 C 260 260 150 340 150 340 C 150 340 40 260 30 210 C 20 160 30 50 30 50 Z" fill="#FACC15" stroke="#EAB308" stroke-width="4"/>
            <!-- Fondo interno blanco -->
            <path d="M 150 22 L 255 58 C 255 58 263 155 255 198 C 247 241 150 318 150 318 C 150 318 53 241 45 198 C 37 155 45 58 45 58 Z" fill="#FFFFFF"/>
            <!-- Área inferior vinotinto -->
            <path d="M 45 198 C 37 155 45 125 45 125 L 255 125 C 255 125 263 155 255 198 C 247 241 150 318 150 318 C 150 318 53 241 45 198 Z" fill="#800000"/>
            <!-- Texto de especialidades -->
            <text x="150" y="52" font-family="Arial, sans-serif" font-size="9" font-weight="bold" fill="#000" text-anchor="middle">INFORMÁTICA,</text>
            <text x="150" y="63" font-family="Arial, sans-serif" font-size="9" font-weight="bold" fill="#000" text-anchor="middle">COMERCIO Y SERVICIOS</text>
            <text x="150" y="74" font-family="Arial, sans-serif" font-size="9" font-weight="bold" fill="#000" text-anchor="middle">ADMINISTRATIVOS</text>
            <!-- Logo geométrico rojo -->
            <g transform="translate(65, 80) scale(0.6)">
                <rect x="0" y="0" width="70" height="50" fill="#800000" />
                <rect x="10" y="10" width="50" height="30" fill="#FFFFFF" />
                <rect x="20" y="15" width="30" height="20" fill="#800000" />
            </g>
            <!-- Cinta amarilla diagonal -->
            <polygon points="40,185 260,135 265,160 35,210" fill="#FACC15" stroke="#EAB308" stroke-width="1"/>
            <text x="150" y="177" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#800000" text-anchor="middle" transform="rotate(-11 150 177)">E.T.C.R.Z. "PEDRO GARCÍA LEAL"</text>
            <!-- Libro abierto -->
            <g transform="translate(110, 205) scale(0.8)">
                <path d="M 10 20 Q 25 10 50 20 L 50 50 Q 25 40 10 50 Z" fill="#FFFFFF" stroke="#800000" stroke-width="2"/>
                <path d="M 50 20 Q 75 10 90 20 L 90 50 Q 75 40 50 50 Z" fill="#FFFFFF" stroke="#800000" stroke-width="2"/>
                <text x="28" y="28" font-family="Arial, sans-serif" font-size="7" font-weight="bold" fill="#800000">FUNDADO</text>
                <text x="35" y="36" font-family="Arial, sans-serif" font-size="7" font-weight="bold" fill="#800000">EL</text>
                <text x="18" y="44" font-family="Arial, sans-serif" font-size="6.5" font-weight="bold" fill="#800000">01-10-1975</text>
            </g>
            <!-- Texto inferior Valera -->
            <text x="150" y="295" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#FFFFFF" text-anchor="middle">VALERA EDO TRUJILLO</text>
        </svg>
    </template>

    <div id="app-container" class="flex w-full h-full">
        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl z-30 transition-all duration-300 absolute lg:relative h-full">
            <div class="p-6 flex items-center justify-between lg:justify-start gap-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <!-- Escudo Institucional Real -->
                    <div class="w-12 h-14 bg-white rounded-xl flex items-center justify-center p-1 shadow-lg shadow-amber-500/20 shrink-0 border border-slate-700 overflow-hidden">
                        <img src="<?= $logo_path; ?>" onerror="this.outerHTML=document.getElementById('escudo-svg').innerHTML;" alt="Escudo Escuela Técnica" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-xs font-bold uppercase text-slate-300 leading-tight">Escuela Técnica</h1>
                        <p class="text-xs text-amber-400 font-semibold">Pedro Garcia Leal</p>
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
                
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Trámites y Planillas</div>
                <a href="generar-planilla.php" class="flex items-center gap-3 px-4 py-3 bg-amber-500 text-slate-900 font-bold rounded-xl shadow-md transition-colors">
                    <i class="fa-solid fa-file-invoice w-5"></i> Planilla de Inscripción
                </a>
                
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 pt-4 mb-2">Módulos del Sistema</div>
                
                
                <a href="chatbot.php" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-slate-800 hover:text-white rounded-xl transition-colors">
                    <i class="fa-solid fa-robot w-5 text-slate-400"></i> Centro de Ayuda
                </a>
            </nav>
        </aside>

        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden backdrop-blur-sm"></div>

        
        <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-6 lg:px-8 z-10 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-amber-500 text-xl p-2 rounded-lg hover:bg-slate-100">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800"><?= $modulo_actual; ?></h2>
                        <p class="text-xs text-slate-500">Generación, edición y descarga oficial de formato escolar</p>
                    </div>
                </div>
                
                
                <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 pl-4 pr-2 py-1.5 rounded-2xl shadow-sm">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nombre_actual); ?></p>
                        <span class="inline-block bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                            <?= htmlspecialchars($rol_actual); ?>
                        </span>
                    </div>
                    
                    <div class="w-10 h-12 rounded-xl bg-white border border-amber-500/40 p-1 shadow-sm overflow-hidden flex items-center justify-center shrink-0">
                        <img src="<?= $logo_path; ?>" onerror="this.outerHTML=document.getElementById('escudo-svg').innerHTML;" alt="Escudo Pedro García Leal" class="w-full h-full object-contain">
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8 w-full">
                <div class="max-w-4xl mx-auto space-y-6 print-wrapper">

                    
                    <div class="no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4 slide-up">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center font-bold">
                                <i class="fa-solid fa-print text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">Acciones de Documento</h3>
                                <p class="text-xs text-slate-500">Llene los datos y elija una opción para exportar.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="descargarPDF()" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                            </button>
                            <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold px-5 py-2.5 rounded-xl transition-all shadow-md flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>

                    
                    <div id="contenidoPDF" class="bg-white p-4 sm:p-6 rounded-2xl shadow-sm border border-slate-200 slide-up">
                        
                        
                        <div class="text-center border-b-2 border-amber-500 pb-3 mb-3">
                            <div class="flex items-center justify-center gap-4 mb-1">
                                <div class="w-14 h-16 shrink-0">
                                    <img src="<?= $logo_path; ?>" onerror="this.outerHTML=document.getElementById('escudo-svg').innerHTML;" alt="Escudo Oficial" class="w-full h-full object-contain">
                                </div>
                                <div>
                                    <h1 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-tight">ESCUELA TÉCNICA COMERCIAL ROBINSONIANA ZAMORANA</h1>
                                    <h2 class="text-sm font-black text-amber-600 uppercase tracking-tight">"PEDRO GARCÍA LEAL"</h2>
                                    <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Valera, Estado Trujillo - Planilla de Inscripción 2024-2025</p>
                                </div>
                            </div>
                        </div>

                        <form onsubmit="return false;" class="space-y-2.5">
                            
                            
                            <div>
                                <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-graduate"></i> Datos del Estudiante
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Nombres:</label>
                                        <input type="text" placeholder="Nombre completo" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Apellidos:</label>
                                        <input type="text" placeholder="Apellidos" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Cédula:</label>
                                        <input type="text" placeholder="V-000.000.000" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Fecha de Nacimiento:</label>
                                        <input type="date" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Género:</label>
                                        <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                            <option value="">-- Selecciona --</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Lugar de Nacimiento:</label>
                                        <input type="text" placeholder="Ciudad, Estado" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Nacionalidad:</label>
                                        <input type="text" placeholder="Ej: Venezolana" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                </div>
                            </div>

                            
                            <div>
                                <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-address-book"></i> Datos de Contacto
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Teléfono Móvil:</label>
                                        <input type="text" placeholder="+58 414 000-0000" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Teléfono Fijo:</label>
                                        <input type="text" placeholder="+58 271 000-0000" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Correo Electrónico:</label>
                                        <input type="email" placeholder="correo@ejemplo.com" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Dirección:</label>
                                    <input type="text" placeholder="Calle, número, sector, ciudad" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                </div>
                            </div>

                            
                            <div>
                                <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-book-bookmark"></i> Información Académica
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Año a Cursar:</label>
                                        <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                            <option value="">-- Selecciona --</option>
                                            <option value="1">1er Año</option>
                                            <option value="2">2do Año</option>
                                            <option value="3">3er Año</option>
                                            <option value="4">4to Año</option>
                                            <option value="5">5to Año</option>
                                            <option value="6">6to Año</option>
                                        </select>
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Especialidad:</label>
                                        <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                            <option value="Informatica">Informática</option>
                                            <option value="Comercio">Comercio</option>
                                            <option value="Servicios Administrativos">Servicios Administrativos</option>
                                        </select>
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Institución Anterior:</label>
                                        <input type="text" placeholder="Nombre institución" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Promedio Anterior:</label>
                                        <input type="number" id="promedio_anterior" placeholder="Ej: 18.5" step="0.1" min="0" max="20" oninput="validarPromedio(this)" onblur="validarPromedioMinimo(this)" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                </div>
                            </div>

                            
                            <div>
                                <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-users-rectangle"></i> Datos del Representante
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 mb-2">
                                    <div class="flex flex-col sm:col-span-2">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Nombres y Apellidos:</label>
                                        <input type="text" placeholder="Nombre completo" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Cédula:</label>
                                        <input type="text" placeholder="V-000.000.000" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Parentesco:</label>
                                        <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                            <option value="">-- Selecciona --</option>
                                            <option value="padre">Padre</option>
                                            <option value="madre">Madre</option>
                                            <option value="abuelo">Abuelo/a</option>
                                            <option value="tutor">Tutor Legal</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Teléfono:</label>
                                        <input type="text" placeholder="+58 414 000-0000" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Ocupación:</label>
                                        <input type="text" placeholder="Profesión u oficio" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5">Dirección del Representante:</label>
                                        <input type="text" placeholder="Calle, número, sector, ciudad" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500 font-medium">
                                    </div>
                                </div>
                            </div>

                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-notes-medical"></i> Salud
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Alergias:</label>
                                            <input type="text" placeholder="Alergias" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Medicamentos:</label>
                                            <input type="text" placeholder="Medicamentos" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Condiciones Médicas Especiales:</label>
                                        <input type="text" placeholder="Asma, diabetes, etc." class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                    </div>
                                </div>

                                <div>
                                    <div class="bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-file-signature"></i> Autorizaciones
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Extracurriculares:</label>
                                            <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                                <option value="">--</option>
                                                <option value="si">Sí</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Excursiones:</label>
                                            <select class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                                <option value="">--</option>
                                                <option value="si">Sí</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-700 uppercase mb-0.5 block">Observaciones Adicionales:</label>
                                        <input type="text" placeholder="Observaciones adicionales" class="w-full px-2 py-1 rounded-lg border border-slate-300 bg-slate-50 text-[11px] text-slate-800 focus:bg-white">
                                    </div>
                                </div>
                            </div>

                            
                            <div class="pt-4 page-break">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div class="border-t border-slate-400 pt-1 text-[10px] font-bold text-slate-700 min-h-[40px] flex items-end justify-center">
                                        Firma del Representante
                                    </div>
                                    <div class="border-t border-slate-400 pt-1 text-[10px] font-bold text-slate-700 min-h-[40px] flex items-end justify-center">
                                        Firma del Estudiante
                                    </div>
                                    <div class="border-t border-slate-400 pt-1 text-[10px] font-bold text-slate-700 min-h-[40px] flex items-end justify-center">
                                        Sello Institucional
                                    </div>
                                </div>
                            </div>

                            
                            <div class="text-center border-t border-slate-200 pt-2 text-[10px] text-slate-500">
                                <p class="font-medium">E.T.C.R.Z. Pedro Garcia Leal - Valera, Edo. Trujillo | Año Escolar 2024-2025 | Fecha de Emisión: <?= date('d/m/Y'); ?></p>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }

        
        function validarPromedio(input) {
            let val = parseFloat(input.value);
            if (val > 20) {
                input.value = 20;
            }
        }

        function validarPromedioMinimo(input) {
            let val = parseFloat(input.value);
            if (val < 0) {
                input.value = 0;
            }
        }

        function descargarPDF() {
            const elemento = document.getElementById('contenidoPDF');
            const opciones = {
                margin: [3, 3, 3, 3],
                filename: 'Planilla_Inscripcion_' + new Date().toISOString().split('T')[0] + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                },
                jsPDF: { 
                    orientation: 'portrait', 
                    unit: 'mm', 
                    format: 'a4',
                    compress: true
                }
            };

            html2pdf().set(opciones).from(elemento).save();
        }
    </script>
</body>
</html>
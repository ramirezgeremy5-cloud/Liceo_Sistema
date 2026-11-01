<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/db-functions.php';

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";
$modulo_actual = "Centro de Ayuda Inteligente";
$rol_actual = $_SESSION['rol'] ?? 'Usuario';
$año_actual = date("Y");

$respuestas_frecuentes = [
    [
        'pregunta' => 'Resumen: Módulo Noticias',
        'respuesta' => 'El módulo NOTICIAS es el centro de comunicación de la institución. Todos los usuarios (estudiantes, representantes, administradores) pueden acceder a él. Contiene: 1) Noticias académicas sobre calendario escolar, exámenes y eventos educativos, 2) Novedades sobre pasantías y oportunidades laborales, 3) Información de labor social y actividades comunitarias, 4) Comunicados informativos generales de la escuela. Las noticias se pueden filtrar por categoría para encontrar información específica. Se actualiza constantemente con eventos y fechas importantes de 2026.',
        'categoria' => 'Noticias'
    ],
    [
        'pregunta' => 'Resumen: Módulo Pasantías',
        'respuesta' => 'El módulo PASANTÍAS está diseñado para gestionar las prácticas profesionales de estudiantes de 3er año. Accesible solo para administradores y representantes. Incluye: 1) Descarga de planillas de control de asistencia para seguimiento diario, 2) Formato de evaluación de tutores académicos, 3) Control de asistencia del pasante en la empresa, 4) Evaluación de tutor empresarial, 5) Normativa completa de pasantías con requisitos, derechos y deberes. Los documentos están organizados y disponibles para descargar. Actualmente en proceso 2026.',
        'categoria' => 'Pasantías'
    ],
    [
        'pregunta' => 'Resumen: Módulo Labor Social',
        'respuesta' => 'El módulo LABOR SOCIAL registra el servicio comunitario que realizan los estudiantes. Accesible para administradores y representantes. Funcionalidades: 1) Descarga de formato de control de horas (para registrar horas de servicio), 2) Formato de informe de labor social (para documentar actividades realizadas), 3) Carga y descarga de documentos de control, 4) Seguimiento de cumplimiento de horas requeridas. Los estudiantes deben completar un mínimo de horas de servicio comunitario durante el año lectivo 2026. Los documentos facilitan el registro y validación de estas actividades.',
        'categoria' => 'Labor Social'
    ],
    [
        'pregunta' => 'Resumen: Módulo Representante',
        'respuesta' => 'El módulo REPRESENTANTE es el portal de información para padres y representantes. Accesible solo para administradores y representantes. Proporciona: 1) Información general de la institución (misión, visión, autoridades), 2) Horarios escolares completos por nivel y sección, 3) Contacto de directivos y personal administrativo, 4) Descarga de planilla de inscripción del estudiante (PDF editable e imprimible) con todos los datos requeridos. Es la ventana principal para que los representantes conozcan información institucional importante durante 2026.',
        'categoria' => 'Representante'
    ],
    [
        'pregunta' => 'Resumen: Módulo Gestión de Estudiantes',
        'respuesta' => 'El módulo GESTIÓN DE ESTUDIANTES es la base de datos central de la institución. Accesible solo para administradores. Permite: 1) Crear nuevo estudiante con cédula, nombre, apellido y otros datos, 2) Ver lista completa de todos los estudiantes activos, 3) Editar información de estudiantes (género, fecha nacimiento, nivel académico, sección, email, teléfono, dirección, ciudad, estado civil), 4) Eliminar registros de estudiantes inactivos. Todos los datos se guardan en la base de datos MySQL. Es esencial mantener esta información actualizada durante 2026.',
        'categoria' => 'Estudiantes'
    ],
    [
        'pregunta' => 'Resumen: Módulo Asistencias con QR',
        'respuesta' => 'El módulo ASISTENCIAS registra la asistencia diaria de estudiantes usando códigos QR. Accesible solo para administradores. Características: 1) Generar código QR que los estudiantes escanean con el teléfono, 2) Estudiante ingresa su cédula y automáticamente se registra con fecha y hora exacta, 3) Ver registros del día en tiempo real, 4) Estadísticas mensuales con porcentaje de asistencia de cada estudiante, 5) Previene duplicados (un estudiante solo puede registrarse una vez por día). Reportes automáticos para 2026. Facilita el control y seguimiento de asistencia.',
        'categoria' => 'Asistencias'
    ],
    [
        'pregunta' => 'Resumen: Centro de Ayuda (este módulo)',
        'respuesta' => 'El CENTRO DE AYUDA es el chatbot inteligente del sistema. Accesible para todos los usuarios. Funciona: 1) Preguntas frecuentes sobre cada módulo del sistema, 2) Explicación detallada del funcionamiento de cada sección, 3) Búsqueda de estudiantes y representantes (según permisos), 4) Información de acceso y credenciales, 5) Solución de problemas comunes. Este módulo facilita la navegación y responde dudas sin necesidad de contactar al administrador. Disponible en 2026 para ayuda rápida.',
        'categoria' => 'Centro de Ayuda'
    ],
    [
        'pregunta' => '¿Cómo registrar un nuevo estudiante?',
        'respuesta' => 'Solo los administradores pueden registrar estudiantes. Ve al módulo "Gestión de Estudiantes" y haz clic en "Nuevo Estudiante". Completa todos los campos requeridos (cédula, nombre, apellido) y haz clic en "Agregar Estudiante". La información se guarda automáticamente en la base de datos. Puedes editar o eliminar estudiantes desde la lista.',
        'categoria' => 'Estudiantes'
    ],
    [
        'pregunta' => '¿Cómo acceder a las noticias?',
        'respuesta' => 'Todos los usuarios pueden ver noticias. Ve al módulo "Noticias" desde la página principal. Verás 6 noticias principales sobre académica, pasantías, labor social e información general. Puedes filtrar por categoría (Académico, Pasantías, Labor Social, Informativo) para encontrar lo que buscas. Las noticias se actualizan constantemente.',
        'categoria' => 'Noticias'
    ],
    [
        'pregunta' => '¿Cómo usar el código QR para asistencia?',
        'respuesta' => 'El administrador genera un código QR en el módulo "Asistencias". Los estudiantes escanean el QR con sus teléfonos (cámara o app de QR). Se abre automáticamente una página donde ingresan su número de cédula. El sistema registra automáticamente su asistencia con fecha y hora. Cada estudiante solo puede registrarse una vez por día.',
        'categoria' => 'Asistencias'
    ],
    [
        'pregunta' => '¿Cuál es mi contraseña?',
        'respuesta' => 'Tu contraseña es asignada por el administrador al crear tu usuario. Las credenciales de acceso son: Usuario: admin/Contraseña: admin123 (Administrador), Usuario: representante/Contraseña: representante123 (Representante), Usuario: usuario/Contraseña: usuario123 (Usuario normal). Si olvidas tu contraseña, contacta al administrador del sistema.',
        'categoria' => 'Seguridad'
    ]
];

$resultados_busqueda = [];
$termino_busqueda = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar'])) {
    $termino_busqueda = htmlspecialchars($_POST['termino']);
    
    if (!empty($termino_busqueda)) {
        // Buscar en preguntas frecuentes
        foreach ($respuestas_frecuentes as $item) {
            if (stripos($item['pregunta'], $termino_busqueda) !== false || 
                stripos($item['respuesta'], $termino_busqueda) !== false) {
                $resultados_busqueda[] = $item;
            }
        }
        
        // Buscar estudiantes (si el usuario tiene permisos)
        if ($rol_actual === 'Administrador' || $rol_actual === 'Representante') {
            $estudiantes = buscarEstudiantes($conexion, $termino_busqueda);
            if (!empty($estudiantes)) {
                $resultados_busqueda[] = [
                    'tipo' => 'estudiante',
                    'datos' => $estudiantes
                ];
            }
        }
        
        // Buscar representantes (si el usuario tiene permisos)
        if ($rol_actual === 'Administrador') {
            $representantes = buscarRepresentantes($conexion, $termino_busqueda);
            if (!empty($representantes)) {
                $resultados_busqueda[] = [
                    'tipo' => 'representante',
                    'datos' => $representantes
                ];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $modulo_actual; ?> - <?php echo $nombre_institucion; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .chatbot-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .search-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
            padding: 12px;
            border: 2px solid #f3e8d8;
            border-radius: 6px;
            font-size: 16px;
        }

        .search-form input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .search-form button {
            padding: 12px 30px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .search-form button:hover {
            background: #6b4423;
        }

        .faq-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .faq-title {
            color: #6b4423;
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 10px;
        }

        .faq-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #fffbf0;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .faq-item:hover {
            background: #fef3c7;
            transform: translateX(5px);
        }

        .faq-pregunta {
            color: #6b4423;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .faq-respuesta {
            color: #8b7355;
            font-size: 14px;
            display: none;
        }

        .faq-respuesta.active {
            display: block;
        }

        .categoria-badge {
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 8px;
        }

        .resultados-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .resultado-item {
            padding: 15px;
            background: #f0f0f0;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .tabla-resultados {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }

        .tabla-resultados th {
            background: #6b4423;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .tabla-resultados td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .tabla-resultados tr:hover {
            background: #fffbf0;
        }

        .no-resultados {
            text-align: center;
            color: #999;
            padding: 30px;
        }

        .volver-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6b4423;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .volver-btn:hover {
            background: #4a2f1a;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><?php echo $nombre_institucion; ?></h1>
            <p>Módulo: <?php echo $modulo_actual; ?></p>
            <div class="user-info" style="position: absolute; top: 20px; right: 20px; text-align: right; color: white;">
                <p style="margin: 0; font-size: 14px;">Rol: <strong><?php echo htmlspecialchars($rol_actual); ?></strong></p>
                <form method="POST" action="../cerrar-sesion.php" style="margin-top: 8px;">
                    <button type="submit" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.5); padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="chatbot-container">
        <a href="../index.php" class="volver-btn">← Volver al Inicio</a>

        <div class="search-section">
            <h2 style="color: #6b4423; margin-top: 0;">Buscar Información</h2>
            <form method="POST" class="search-form">
                <input type="text" name="termino" placeholder="Busca preguntas, estudiantes, representantes..." value="<?php echo $termino_busqueda; ?>" required>
                <button type="submit" name="buscar">Buscar</button>
            </form>
        </div>

        <?php if (!empty($resultados_busqueda)): ?>
            <div class="resultados-section">
                <h3 style="color: #6b4423;">Resultados de Búsqueda</h3>
                
                <?php foreach ($resultados_busqueda as $resultado): ?>
                    <?php if (isset($resultado['tipo'])): ?>
                        <?php if ($resultado['tipo'] === 'estudiante'): ?>
                            <h4 style="color: #f59e0b; margin-top: 20px;">Estudiantes Encontrados:</h4>
                            <table class="tabla-resultados">
                                <thead>
                                    <tr>
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Nivel</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultado['datos'] as $est): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($est['cedula']); ?></td>
                                            <td><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($est['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($est['nivel_academico'] ?? 'N/A'); ?></td>
                                            <td><span style="background: <?php echo ($est['estado'] === 'Activo') ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($est['estado'] === 'Activo') ? '#065f46' : '#991b1b'; ?>; padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($est['estado']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($resultado['tipo'] === 'representante'): ?>
                            <h4 style="color: #f59e0b; margin-top: 20px;">Representantes Encontrados:</h4>
                            <table class="tabla-resultados">
                                <thead>
                                    <tr>
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Relación</th>
                                        <th>Teléfono</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultado['datos'] as $rep): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rep['cedula']); ?></td>
                                            <td><?php echo htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($rep['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($rep['relacion'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($rep['telefono'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="resultado-item">
                            <div class="faq-pregunta">❓ <?php echo htmlspecialchars($resultado['pregunta']); ?></div>
                            <div class="faq-respuesta active">
                                <p><?php echo htmlspecialchars($resultado['respuesta']); ?></p>
                                <span class="categoria-badge"><?php echo htmlspecialchars($resultado['categoria']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="faq-section">
            <div class="faq-title">Preguntas Frecuentes</div>
            
            <?php foreach ($respuestas_frecuentes as $index => $item): ?>
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-pregunta">❓ <?php echo htmlspecialchars($item['pregunta']); ?></div>
                    <div class="faq-respuesta">
                        <p><?php echo htmlspecialchars($item['respuesta']); ?></p>
                        <span class="categoria-badge"><?php echo htmlspecialchars($item['categoria']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo $año_actual; ?> <?php echo $nombre_institucion; ?> - Todos los derechos reservados.</p>
    </footer>

    <script>
        function toggleFAQ(element) {
            const respuesta = element.querySelector('.faq-respuesta');
            respuesta.classList.toggle('active');
        }
    </script>
</body>
</html>

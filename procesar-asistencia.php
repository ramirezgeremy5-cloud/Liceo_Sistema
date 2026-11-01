<?php
session_start();

require_once 'includes/config.php';
require_once 'includes/db-functions.php';

// Obtener cédula del GET o POST
$cedula = $_GET['cedula'] ?? $_POST['cedula'] ?? '';
$resultado_registro = null;
$error_registro = null;

// Si se envía cédula por GET (desde código QR con datos), registrar automáticamente
if ($_GET && isset($_GET['cedula']) && !empty($_GET['cedula'])) {
    if (!$conexion) {
        $error_registro = "Error de conexión a base de datos.";
    } else {
        $cedula_escape = $conexion->real_escape_string($cedula);
        $sql = "SELECT id, nombre, apellido FROM estudiantes WHERE cedula = '$cedula_escape' AND estado = 'Activo'";
        $resultado = $conexion->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $estudiante = $resultado->fetch_assoc();
            $id_estudiante = $estudiante['id'];
            
            if (registrarAsistencia($conexion, $id_estudiante)) {
                $resultado_registro = [
                    'exito' => true,
                    'nombre' => htmlspecialchars($estudiante['nombre']),
                    'apellido' => htmlspecialchars($estudiante['apellido'])
                ];
            } else {
                $error_registro = "Ya habías registrado tu asistencia hoy.";
            }
        } else {
            $error_registro = "Cédula no encontrada o estudiante inactivo.";
        }
    }
}

// Si es POST, devolver JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $cedula_post = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    
    if (empty($cedula_post)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Debes ingresar tu cédula.']);
        exit;
    }
    
    if (!$conexion) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error de conexión a base de datos.']);
        exit;
    }
    
    $cedula_escape = $conexion->real_escape_string($cedula_post);
    $sql = "SELECT id, nombre, apellido FROM estudiantes WHERE cedula = '$cedula_escape' AND estado = 'Activo'";
    $resultado = $conexion->query($sql);
    
    if (!$resultado || $resultado->num_rows === 0) {
        echo json_encode(['exito' => false, 'mensaje' => 'Cédula no encontrada o estudiante inactivo.']);
        exit;
    }
    
    $estudiante = $resultado->fetch_assoc();
    $id_estudiante = $estudiante['id'];
    
    if (registrarAsistencia($conexion, $id_estudiante)) {
        echo json_encode([
            'exito' => true, 
            'mensaje' => 'Bienvenido ' . htmlspecialchars($estudiante['nombre']) . ' ' . htmlspecialchars($estudiante['apellido']) . '. Tu asistencia ha sido registrada.'
        ]);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Ya habías registrado tu asistencia hoy.']);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Registrar Asistencia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .logo-escuela {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }
        
        h1 {
            color: #6b4423;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .subtitulo {
            color: #999;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* ANIMACIÓN DE CHECKMARK */
        .check-circle {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 30px auto;
            display: none;
        }

        .check-circle.show {
            display: block;
        }

        .check-background {
            position: absolute;
            width: 100%;
            height: 100%;
            background: #10b981;
            border-radius: 50%;
            animation: scaleUp 0.6s ease-out;
        }

        .checkmark {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkmark svg {
            width: 80%;
            height: 80%;
            stroke: white;
            stroke-width: 2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .checkmark-path {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: drawCheck 0.8s ease-out forwards;
            animation-delay: 0.2s;
        }

        @keyframes scaleUp {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .mensaje-exito {
            display: none;
            background: #d1fae5;
            color: #065f46;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #6ee7b7;
        }

        .mensaje-exito.show {
            display: block;
            animation: bounce 0.6s ease-out;
        }

        .mensaje-exito h3 {
            margin: 0 0 8px;
            font-size: 18px;
            color: #065f46;
        }

        .mensaje-exito p {
            margin: 0;
            font-size: 14px;
        }
        
        .form-section {
            display: block;
        }

        .form-section.hidden {
            display: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #6b4423;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 14px;
            border: 2px solid #f3e8d8;
            border-radius: 10px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
            background: #fffbf0;
        }

        input::placeholder {
            color: #ccc;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background: #6b4423;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        button:active {
            transform: translateY(0);
        }
        
        .info {
            background: #d1fae5;
            color: #065f46;
            padding: 14px;
            border-radius: 10px;
            border: 2px solid #6ee7b7;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }

        .error-msg {
            display: none;
            background: #fee2e2;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #fca5a5;
        }

        .error-msg.show {
            display: block;
            animation: shake 0.4s ease-out;
        }

        .error-msg h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .error-msg p {
            margin: 0;
            font-size: 14px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .hora {
            text-align: center;
            color: #666;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .hora strong {
            display: block;
            font-size: 18px;
            color: #6b4423;
            margin-bottom: 5px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3e8d8;
            border-top: 4px solid #f59e0b;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            h1 {
                font-size: 24px;
            }

            .check-circle {
                width: 100px;
                height: 100px;
                margin: 20px auto;
            }

            button {
                padding: 12px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-escuela">✓</div>
        <h1>Registrar Asistencia</h1>
        <p class="subtitulo">Escuela Técnica Pedro Garcia Leal</p>

        <!-- CHECK MARK ANIMADO -->
        <div class="check-circle" id="checkCircle">
            <div class="check-background"></div>
            <div class="checkmark">
                <svg viewBox="0 0 50 50">
                    <polyline class="checkmark-path" points="12,26 20,35 38,15"></polyline>
                </svg>
            </div>
        </div>

        <!-- MENSAJE DE ÉXITO -->
        <div class="mensaje-exito" id="mensajeExito">
            <h3>¡Escaneo Exitoso!</h3>
            <p id="textoExito"></p>
        </div>

        <!-- MENSAJE DE ERROR -->
        <div class="error-msg" id="mensajeError">
            <h3>Error al Registrar</h3>
            <p id="textoError"></p>
        </div>

        <!-- FORMULARIO -->
        <div class="form-section" id="formSection">
            <div class="info">
                Ingresa tu número de cédula para registrar tu asistencia a clases.
            </div>
            
            <form id="formularioAsistencia" method="POST">
                <div class="form-group">
                    <label for="cedula">Número de Cédula:</label>
                    <input type="text" id="cedula" name="cedula" placeholder="Ej: 12345678" required autofocus inputmode="numeric">
                </div>
                
                <button type="submit">Registrar Asistencia</button>
            </form>
        </div>

        <!-- LOADING -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 15px; color: #999;">Registrando...</p>
        </div>
        
        <div class="hora">
            <strong id="horaActual"></strong>
            <p id="fechaActual"></p>
        </div>
    </div>
    
    <script>
        // Actualizar hora y fecha
        function actualizarHora() {
            const ahora = new Date();
            const hora = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const fecha = ahora.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('horaActual').textContent = hora;
            document.getElementById('fechaActual').textContent = fecha;
        }
        
        actualizarHora();
        setInterval(actualizarHora, 1000);

        // Verificar si hay resultado desde GET
        <?php if ($resultado_registro): ?>
            mostrarExito('Bienvenido <?php echo $resultado_registro['nombre']; ?> <?php echo $resultado_registro['apellido']; ?>. Tu asistencia ha sido registrada.');
        <?php elseif ($error_registro): ?>
            mostrarError('<?php echo $error_registro; ?>');
        <?php endif; ?>

        function mostrarExito(mensaje) {
            document.getElementById('formSection').classList.add('hidden');
            document.getElementById('loading').style.display = 'none';
            document.getElementById('checkCircle').classList.add('show');
            document.getElementById('textoExito').textContent = mensaje;
            document.getElementById('mensajeExito').classList.add('show');
            
            setTimeout(() => {
                document.getElementById('formSection').classList.remove('hidden');
                document.getElementById('mensajeExito').classList.remove('show');
                document.getElementById('checkCircle').classList.remove('show');
                document.getElementById('cedula').value = '';
                document.getElementById('cedula').focus();
            }, 4000);
        }

        function mostrarError(mensaje) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('textoError').textContent = mensaje;
            document.getElementById('mensajeError').classList.add('show');
            document.getElementById('cedula').value = '';
            document.getElementById('cedula').focus();
            
            setTimeout(() => {
                document.getElementById('mensajeError').classList.remove('show');
            }, 4000);
        }
        
        // Procesar formulario
        document.getElementById('formularioAsistencia').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const cedula = document.getElementById('cedula').value.trim();
            
            if (!cedula) {
                mostrarError('Debes ingresar tu cédula.');
                return;
            }

            document.getElementById('formSection').classList.add('hidden');
            document.getElementById('loading').style.display = 'block';
            
            // Enviar por AJAX
            fetch('procesar-asistencia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'cedula=' + encodeURIComponent(cedula)
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    mostrarExito(data.mensaje);
                } else {
                    mostrarError(data.mensaje);
                }
            })
            .catch(error => {
                mostrarError('Error de conexión: ' + error);
            });
        });

        // Focus automático en cédula
        document.getElementById('cedula').focus();
    </script>
</body>
</html>

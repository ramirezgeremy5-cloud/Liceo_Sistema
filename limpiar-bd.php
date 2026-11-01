<?php
/**
 * Script para limpiar completamente la base de datos y recrearla
 * Acceso: http://localhost/liceo/limpiar-bd.php
 */

require_once 'includes/config.php';

$mensaje = "";
$tipo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Obtener todas las tablas
    $resultado = @$conexion->query("SHOW TABLES FROM escuela_tecnica");
    
    if ($resultado && $resultado->num_rows > 0) {
        $tablas_eliminadas = [];
        
        while ($row = $resultado->fetch_array()) {
            $tabla = $row[0];
            @$conexion->query("DROP TABLE IF EXISTS `$tabla`");
            $tablas_eliminadas[] = $tabla;
        }
        
        $mensaje = "Se eliminaron " . count($tablas_eliminadas) . " tablas. Ahora se recrearán automáticamente.";
        $tipo = "warning";
        
        // Forzar recarga de config para recrear las tablas
        header("Refresh: 2; url=index.php");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = "Debes confirmar para limpiar la base de datos.";
    $tipo = "error";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar Base de Datos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #0c5460;
            font-size: 14px;
            line-height: 1.6;
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mensaje.warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }

        .mensaje.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .mensaje.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            gap: 8px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-limpiar {
            background: #dc3545;
            color: white;
        }

        .btn-limpiar:hover {
            background: #c82333;
        }

        .btn-limpiar:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-cancelar {
            background: #6c757d;
            color: white;
            text-decoration: none;
            text-align: center;
        }

        .btn-cancelar:hover {
            background: #5a6268;
        }

        .steps {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .steps h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .steps ol {
            margin-left: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }

        .steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Limpiar Base de Datos</h1>
        <p class="subtitle">Herramienta de administración del sistema</p>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="warning-box">
            ⚠️ <strong>Advertencia:</strong> Esta acción eliminará TODAS las tablas de la base de datos. 
            Los datos no podrán recuperarse. Las tablas se recrearán automáticamente con la estructura correcta.
        </div>

        <div class="info-box">
            ℹ️ <strong>Información:</strong> Si la base de datos tiene errores de sintaxis SQL, esta es la forma más rápida 
            de corregirlo. Las tablas se crearán automáticamente con la estructura correcta.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>
                    <input type="checkbox" id="confirmar_checkbox" name="confirmar" value="1">
                    Entiendo que todos los datos serán eliminados y acepto continuar
                </label>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-limpiar" id="limpiar_btn" disabled>
                    🗑️ Limpiar Base de Datos
                </button>
                <a href="index.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>

        <div class="steps">
            <h3>Después de limpiar:</h3>
            <ol>
                <li>Las tablas se recrearán automáticamente</li>
                <li>Serás redirigido al inicio en 2 segundos</li>
                <li>Deberás volver a hacer login</li>
                <li>Los módulos funcionarán sin errores de sintaxis SQL</li>
            </ol>
        </div>
    </div>

    <script>
        document.getElementById('confirmar_checkbox').addEventListener('change', function() {
            document.getElementById('limpiar_btn').disabled = !this.checked;
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!document.getElementById('confirmar_checkbox').checked) {
                e.preventDefault();
                alert('Debes confirmar antes de continuar');
            }
        });
    </script>
</body>
</html>

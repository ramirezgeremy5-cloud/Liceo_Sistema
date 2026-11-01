<?php
require_once 'includes/auth.php';
requerirLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Autorizado - Escuela Técnica Pedro Garcia Leal</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .error-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #fffbf0 0%, #fef3c7 100%);
            padding: 20px;
        }

        .error-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
        }

        .error-box h1 {
            color: #dc2626;
            font-size: 48px;
            margin: 0 0 10px 0;
        }

        .error-box p {
            color: #6b4423;
            font-size: 16px;
            margin: 10px 0;
        }

        .btn-volver {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-box">
            <h1>⚠️ Acceso Denegado</h1>
            <p>No tienes permiso para acceder a esta página.</p>
            <p>Tu rol actual es: <strong><?php echo htmlspecialchars(obtenerRol()); ?></strong></p>
            <a href="index.php" class="btn-volver">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>

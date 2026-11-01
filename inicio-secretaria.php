<?php
session_start();

// Validar que el usuario haya iniciado sesión y sea exactamente la secretaria
// Usamos 'Secretaria' (con mayúscula inicial) porque así está definido en tu login.php
if (!isset($_SESSION['usuario_logueado']) || $_SESSION['rol'] !== 'Secretaria') {
    // Si no está logueada o intenta entrar alguien más, lo enviamos al login
    header("Location: login.php");
    exit();
}

// Obtenemos el nombre del usuario de la sesión, si no existe usamos un predeterminado
$nombre_usuario = $_SESSION['nombre'] ?? 'Secretaria';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Secretaria - Escuela Técnica Pedro Garcia Leal</title>
    
    <!-- Enlazamos a tu hoja de estilos principal -->
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f7f6; 
            margin: 0; 
        }
        
        .navbar { 
            background: linear-gradient(135deg, #6b4423 0%, #8b6f47 100%); 
            color: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
        }
        
        .navbar h2 { 
            margin: 0; 
            font-size: 20px; 
        }
        
        .user-controls { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-size: 14px; 
        }
        
        .btn-logout { 
            color: white; 
            text-decoration: none; 
            padding: 8px 15px; 
            background: rgba(255,255,255,0.2); 
            border: 1px solid rgba(255,255,255,0.5); 
            border-radius: 4px; 
            transition: all 0.3s; 
        }
        
        .btn-logout:hover { 
            background: rgba(255,255,255,0.3); 
        }
        
        .container { 
            max-width: 1000px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }
        
        .welcome-header { 
            margin-bottom: 30px; 
            color: #6b4423; 
            border-bottom: 2px solid #f3e8d8;
            padding-bottom: 10px;
        }
        
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; 
        }
        
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            text-align: center; 
            transition: transform 0.3s, box-shadow 0.3s; 
            border-top: 4px solid #f59e0b; 
        }
        
        .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
        }
        
        .card h3 { 
            color: #6b4423; 
            margin-top: 0; 
            font-size: 22px; 
        }
        
        .card p { 
            color: #666; 
            font-size: 14px; 
            line-height: 1.5; 
            margin-bottom: 20px; 
            min-height: 42px; /* Alineación uniforme */
        }
        
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #f59e0b; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: bold; 
            transition: all 0.3s; 
        }
        
        .btn:hover { 
            background-color: #d97706; 
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Panel de Secretaria de Dirección</h2>
        <div class="user-controls">
            <span>Bienvenida, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></span>
            <!-- Asegúrate de que cerrar-sesion.php esté en la misma carpeta raíz -->
            <a href="cerrar-sesion.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <h2 class="welcome-header">Módulos Autorizados</h2>
        <div class="dashboard-grid">
            
            <!-- Módulo de Noticias -->
            <div class="card">
                <h3>📰 Gestión de Noticias</h3>
                <p>Crea, edita y publica noticias, anuncios y comunicados para la institución.</p>
                <a href="modulos/noticias.php" class="btn">Ir a Noticias</a>
            </div>

            <!-- Módulo de Pasantías -->
            <div class="card">
                <h3>💼 Pasantías</h3>
                <p>Visualiza el registro y estado de las pasantías de los estudiantes (Solo lectura).</p>
                <a href="modulos/pasantias.php" class="btn">Ver Pasantías</a>
            </div>

            <!-- Módulo de Estudiantes -->
            <div class="card">
                <h3>🎓 Gestión de Estudiantes</h3>
                <p>Consulta el listado y la información general de los estudiantes matriculados (Solo lectura).</p>
                <a href="modulos/gestion-estudiantes.php" class="btn">Ver Estudiantes</a>
            </div>

        </div>
    </div>
</body>
</html>
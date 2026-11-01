<?php
session_start();

// Usuarios del sistema (Respaldo) - Contraseñas simples
$usuarios_legacy = [
    'admin' => [
        'password' => 'admin123',
        'rol' => 'Administrador',
        'nombre' => 'Administrador'
    ],
    'secretaria' => [
        'password' => 'secretaria123',
        'rol' => 'Secretaria',
        'nombre' => 'Secretaria de Dirección'
    ],
    'profesor' => [
        'password' => 'profesor123',
        'rol' => 'Profesor',
        'nombre' => 'Prof. Juan García'
    ],
    'estudiante' => [
        'password' => 'estudiante123',
        'rol' => 'Estudiante',
        'nombre' => 'Estudiante'
    ],
    'representante' => [
        'password' => 'representante123',
        'rol' => 'Representante',
        'nombre' => 'Representante'
    ],
    'usuario' => [
        'password' => 'usuario123',
        'rol' => 'Usuario',
        'nombre' => 'Usuario'
    ],
    'practicas' => [
        'password' => 'practicas123',
        'rol' => 'Prácticas Profesionales',
        'nombre' => 'Coord. de Pasantías'
    ],
    'neriz' => [
        'password' => 'neriz123',
        'rol' => 'Labor Social',
        'nombre' => 'Neriz Teran'
    ],
    'coord' => [
        'password' => 'coord123',
        'rol' => 'coordinador_academico',
        'nombre' => 'Coord. Académico'
    ],
    'ana_martinez' => ['password' => 'ana11612798', 'rol' => 'Profesor', 'nombre' => 'ana_martinez'],
    'bettsymar' => ['password' => 'bettsymar12542399', 'rol' => 'Profesor', 'nombre' => 'Bettsymar Santos'],
    'marielis' => ['password' => 'marielis10765310', 'rol' => 'Profesor', 'nombre' => 'Marielis Duran'],
    'jose_paez' => ['password' => 'jose15176936', 'rol' => 'Profesor', 'nombre' => 'Jose_Paez']
];

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado']) {
    $rol = $_SESSION['rol'] ?? '';
    if ($rol === 'estudiante') {
        header('Location: inicio-estudiante.php');
    } elseif ($rol === 'Secretaria') {
        header('Location: inicio-secretaria.php');
    } elseif ($rol === 'Prácticas Profesionales') {
        header('Location: modulos/pasantias.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = "";

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $login_success = false;
    $user_data = [];

    // 1. Intentar conexión a la Base de Datos
    $host = 'localhost';
    $dbname = 'escuela_tecnica';
    $db_user = 'root';
    $db_pass = '';
    $db_connected = false;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_connected = true;
    } catch (PDOException $e) {
        // Conexión fallida. Se usará el respaldo automáticamente.
    }

    if ($db_connected) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username AND estado = 'Activo'");
            $stmt->execute(['username' => $usuario]);
            $db_row = $stmt->fetch(PDO::FETCH_ASSOC);

            
            if ($db_row && password_verify($password, $db_row['password'])) {
                $login_success = true;
                
                
                $rol_mapped = $db_row['rol'];
                $role_map = [
                    'secretaria_direccion' => 'Secretaria',
                    'coordinador_academico' => 'Coordinador Académico',
                    'profesor' => 'Profesor',
                    'practicas_profesionales' => 'Prácticas Profesionales',
                    'labor_social' => 'Labor Social',
                    'estudiante' => 'Estudiante',
                    'representante' => 'Representante'
                ];
                
                if (array_key_exists($rol_mapped, $role_map)) {
                    $rol_mapped = $role_map[$rol_mapped];
                }

                $user_data = [
                    'rol' => $rol_mapped,
                    'nombre' => $db_row['nombre'] . ' ' . $db_row['apellido'],
                    'id' => $db_row['id'],
                    'username' => $db_row['username']
                ];
            }
        } catch (PDOException $e) {
            
        }
    }

    
    if (!$login_success && isset($usuarios_legacy[$usuario]) && $password === $usuarios_legacy[$usuario]['password']) {
        $login_success = true;
        $user_data = [
            'rol' => $usuarios_legacy[$usuario]['rol'],
            'nombre' => $usuarios_legacy[$usuario]['nombre'],
            'id' => 9999 + array_search($usuario, array_keys($usuarios_legacy)), // ID simulado numérico para compatibilidad
            'username' => $usuario
        ];
    }

    
    if ($login_success) {
        
        $_SESSION['usuario_logueado'] = true;
        $_SESSION['usuario'] = $user_data['username'];
        $_SESSION['rol'] = $user_data['rol'];
        $_SESSION['nombre'] = $user_data['nombre'];
        
        
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        
        
        if ($user_data['rol'] === 'estudiante') {
            header('Location: inicio-estudiante.php');
        } elseif ($user_data['rol'] === 'Secretaria') {
            header('Location: index.php');
        } elseif ($user_data['rol'] === 'Prácticas Profesionales') {
            header('Location: modulos/pasantias.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos, o cuenta inactiva.";
    }
}


$escudo_svg = '
<svg viewBox="0 0 200 240" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M100 8L182 34V110C182 166 142 212 100 232C58 212 18 166 18 110V34L100 8Z" fill="#facc15" stroke="#b45309" stroke-width="6"/>
    <path d="M26 40L100 18L174 40V105C174 155 138 196 100 214C62 196 26 155 26 105V40Z" fill="#ffffff"/>
    <path d="M26 95L174 125V175C174 175 140 210 100 220C60 210 26 175 26 175V95Z" fill="#7f1d1d"/>
    <text x="100" y="50" font-size="12" font-family="Arial, sans-serif" font-weight="900" fill="#0f172a" text-anchor="middle" letter-spacing="0.5">INFORMÁTICA, COMERCIO</text>
    <text x="100" y="66" font-size="12" font-family="Arial, sans-serif" font-weight="900" fill="#0f172a" text-anchor="middle" letter-spacing="0.5">Y SERVICIOS ADMIN.</text>
    <rect x="45" y="78" width="45" height="40" rx="4" fill="#7f1d1d" stroke="#facc15" stroke-width="2.5"/>
    <rect x="53" y="86" width="29" height="24" rx="2" fill="none" stroke="#ffffff" stroke-width="2"/>
    <rect x="61" y="94" width="13" height="8" fill="#ffffff"/>
    <g transform="rotate(-5 100 148)">
        <rect x="25" y="133" width="150" height="28" fill="#facc15" rx="4" stroke="#ca8a04" stroke-width="1.5"/>
        <text x="100" y="152" font-size="12" font-family="Arial, sans-serif" font-weight="900" fill="#7f1d1d" text-anchor="middle">E.T. "PEDRO GARCÍA LEAL"</text>
    </g>
    <g transform="translate(82, 172)">
        <path d="M0 10C10 5 20 10 30 5V25C20 30 10 25 0 30V10Z" fill="#ffffff"/>
        <path d="M30 5C40 10 50 5 60 10V30C50 25 40 30 30 25V5Z" fill="#e2e8f0"/>
        <text x="30" y="14" font-size="6" font-weight="900" fill="#7f1d1d" text-anchor="middle">FUNDADO EL</text>
        <text x="30" y="22" font-size="6" font-weight="900" fill="#7f1d1d" text-anchor="middle">01-10-1975</text>
    </g>
    <ellipse cx="100" cy="218" rx="40" ry="11" fill="#7f1d1d"/>
    <text x="100" y="222" font-size="9" font-family="Arial, sans-serif" font-weight="900" fill="#facc15" text-anchor="middle" letter-spacing="0.5">VALERA EDO TRUJILLO</text>
</svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Escuela Técnica Pedro Garcia Leal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .login-container {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 35px 40px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        
        .login-logo {
            width: 110px;
            height: 130px;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 8px 20px rgba(245, 158, 11, 0.45));
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .login-logo:hover {
            transform: scale(1.08);
        }

        .login-logo svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 23px;
            font-weight: 800;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }

        .login-header p {
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-main);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-main);
            background: var(--bg);
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: var(--surface);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-login:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 14px;
        }

        .credenciales {
            background: var(--bg);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-top: 25px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .credenciales h3 {
            margin: 0 0 10px 0;
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .credenciales p {
            margin: 4px 0;
            font-family: monospace;
        }

        .credenciales strong {
            display: inline-block;
            min-width: 100px;
            color: var(--text-main);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            
            <div class="login-logo">
                <?= $escudo_svg; ?>
            </div>
            <h1>Escuela Técnica</h1>
            <p>Pedro Garcia Leal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        
        <form method="POST" id="formLogin" autocomplete="off">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                
                <input type="password" id="password" name="password" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="credenciales">
            <h3>Niveles de acceso del sistema:</h3>
            <p><strong>Administrador</strong></p>
            <p><strong>Secretaria</strong></p>
            <p><strong>Pasantías</strong></p>
            <p><strong>Estudiante</strong></p>
            <p><strong>Coordinador</strong></p>
            <p><strong>Profesor</strong></p>
        </div>
    </div>

    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('formLogin').reset();
            document.getElementById('usuario').value = '';
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>
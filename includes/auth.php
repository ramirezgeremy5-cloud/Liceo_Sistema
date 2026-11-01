<?php
/**
 * Sistema de Autenticación
 * Gestión de sesiones y roles
 */

session_start();

// Usuarios del sistema con contraseñas encriptadas
$usuarios = [
    'admin' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'rol' => 'Administrador',
        'nombre' => 'Administrador'
    ],
    'secretaria' => [
        'password' => password_hash('secretaria123', PASSWORD_DEFAULT),
        'rol' => 'Secretaria',
        'nombre' => 'Secretaria de Dirección'
    ],
    'representante' => [
        'password' => password_hash('rep123', PASSWORD_DEFAULT),
        'rol' => 'Representante',
        'nombre' => 'Representante'
    ],
    'usuario' => [
        'password' => password_hash('user123', PASSWORD_DEFAULT),
        'rol' => 'Usuario',
        'nombre' => 'Usuario'
    ],
    'practicas' => [
        'password' => password_hash('practicas123', PASSWORD_DEFAULT),
        'rol' => 'Prácticas Profesionales',
        'nombre' => 'Coord. de Pasantías'
    ],
    'neriz' => [
        'password' => password_hash('neriz123', PASSWORD_DEFAULT),
        'rol' => 'Labor Social',
        'nombre' => 'Neriz Teran'
    ]
];

// Función para verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'];
}

// Función para obtener el rol del usuario
function obtenerRol() {
    return isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
}

// Función para verificar permisos
function tienePermiso($rol_requerido) {
    $rol_actual = obtenerRol();
    
    // El usuario de Labor Social (Neriz) tiene acceso a módulos administrativos y gestión
    if ($rol_actual === 'Labor Social') {
        return true; 
    }
    
    if ($rol_requerido === 'admin') {
        return $rol_actual === 'Administrador';
    } elseif ($rol_requerido === 'secretaria') {
        return $rol_actual === 'Secretaria' || $rol_actual === 'Administrador';
    } elseif ($rol_requerido === 'representante') {
        return $rol_actual === 'Representante' || $rol_actual === 'Administrador' || $rol_actual === 'Secretaria';
    } elseif ($rol_requerido === 'usuario') {
        return $rol_actual === 'Usuario' || $rol_actual === 'Representante' || $rol_actual === 'Secretaria' || $rol_actual === 'Administrador';
    }
    
    return false;
}

// Función para cerrar sesión
function cerrarSesion() {
    $_SESSION = [];
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Función para redirigir si no está logueado
function requerirLogin() {
    if (!estaLogueado()) {
        header('Location: ../login.php');
        exit;
    }
}

// Función para redirigir si no tiene permisos
function requerirPermiso($rol) {
    requerirLogin();
    if (!tienePermiso($rol)) {
        header('Location: ../no-autorizado.php');
        exit;
    }
}
?>
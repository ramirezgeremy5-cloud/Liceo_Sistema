<?php
// Obtener la IP local del servidor

function obtenerIPLocal() {
    // Primero intentar con $_SERVER['SERVER_ADDR']
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }
    
    // Si estamos en localhost, intentar detectar la IP real
    if (php_uname('s') === 'Windows NT') {
        // Para Windows
        $output = shell_exec('ipconfig');
        if (preg_match('/IPv4 Address.*:\s*(\d+\.\d+\.\d+\.\d+)/i', $output, $matches)) {
            return $matches[1];
        }
    } else {
        // Para Linux/Mac
        $output = shell_exec("hostname -I 2>/dev/null || ifconfig 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}'");
        if (!empty($output)) {
            $ips = explode(' ', trim($output));
            if (!empty($ips[0]) && $ips[0] !== '127.0.0.1') {
                return $ips[0];
            }
        }
    }
    
    return null;
}

header('Content-Type: application/json');

$ip = obtenerIPLocal();

if ($ip) {
    echo json_encode(['ip' => $ip]);
} else {
    // Si no se puede detectar, devolver localhost como fallback
    echo json_encode(['ip' => null]);
}
?>

<?php
$host = 'localhost';
$dbname = 'escuela_tecnica'; // Asegúrate de que este sea el nombre de tu BD en phpMyAdmin
$username = 'root';        // Usuario por defecto en Laragon/XAMPP
$password = '';            // Contraseña por defecto (vacía en Laragon/XAMPP, 'root' en MAMP)

try {
    // Intentar establecer la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configurar el manejo de errores para que lance excepciones
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurar el modo de fetch por defecto a arreglos asociativos
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Si falla, en lugar de un error fatal (como el que veías), capturamos el error
    // y dejamos $pdo como nulo para que el panel pueda mostrar un mensaje amigable.
    $pdo = null;
    $error_db = "Error de conexión: " . $e->getMessage();
}
?>
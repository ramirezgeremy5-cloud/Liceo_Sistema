<?php
// ==========================================
// 1. CONEXIÓN A LA BASE DE DATOS
// ==========================================
// Nota: Puedes incluir tu archivo de conexión aquí. Ejemplo:
// require_once 'config/database.php';

$host = 'localhost';
$dbname = 'escuela_tecnica';
$username = 'root'; // Reemplaza con tu usuario
$password = ''; // Reemplaza con tu contraseña

try {
    // Usamos PDO por seguridad y buenas prácticas
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// ==========================================
// 2. CONSULTA DE ESTUDIANTES
// ==========================================
// Traemos los campos más importantes según tu estructura SQL
$query = "SELECT id, cedula, nombre, apellido, nivel_academico, seccion, email, telefono, estado 
          FROM estudiantes 
          ORDER BY nombre ASC, apellido ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();

// Obtenemos todos los resultados en un arreglo asociativo
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiantes - Sistema de Gestión Académico</title>
    <!-- Integración de Tailwind CSS para el diseño (puedes enlazar tus propios CSS) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
</body>
</html>
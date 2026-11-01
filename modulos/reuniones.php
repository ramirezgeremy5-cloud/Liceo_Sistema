<?php
session_start();
require_once '../includes/assignments_data.php';
require_once '../includes/reuniones_data.php';

$usuario_actual = $_SESSION['usuario'];
$rol = $_SESSION['rol'];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar'])) {
    $tipo = $_POST['tipo'];
    $data = [
        'titulo' => $_POST['titulo'],
        'fecha' => $_POST['fecha'],
        'detalle' => $_POST['detalle']
    ];

    if ($tipo === 'general' && $rol === 'Secretaria') {
        $_SESSION['reuniones_data']['general'][] = $data;
    } elseif ($tipo === 'profesor') {
        $_SESSION['reuniones_data']['profesores'][$usuario_actual][] = array_merge($data, ['seccion' => $_POST['seccion']]);
    } elseif ($tipo === 'labor_social' && $rol === 'Labor Social') {
        $_SESSION['reuniones_data']['labor_social'][] = $data;
    } elseif ($tipo === 'pasantias' && $rol === 'Prácticas Profesionales') {
        $_SESSION['reuniones_data']['pasantias'][] = $data;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reuniones</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-bold mb-6">Agenda de Reuniones</h1>

        <!-- Formulario según rol -->
        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
            <form method="POST" class="grid grid-cols-1 gap-4">
                <input type="text" name="titulo" placeholder="Título de la reunión" class="p-2 border rounded" required>
                <input type="date" name="fecha" class="p-2 border rounded" required>
                <textarea name="detalle" placeholder="Detalles..." class="p-2 border rounded"></textarea>
                
                <?php if ($rol === 'Secretaria'): ?>
                    <input type="hidden" name="tipo" value="general">
                    <button name="agendar" class="bg-red-600 text-white p-2 rounded">Crear Reunión General</button>
                <?php elseif ($rol === 'Profesor'): ?>
                    <select name="seccion" class="p-2 border rounded" required>
                        <?php foreach(($asignaciones[$usuario_actual] ?? []) as $s) echo "<option value='$s'>$s</option>"; ?>
                    </select>
                    <input type="hidden" name="tipo" value="profesor">
                    <button name="agendar" class="bg-blue-600 text-white p-2 rounded">Agendar para mi sección</button>
                <?php elseif ($rol === 'Labor Social'): ?>
                    <input type="hidden" name="tipo" value="labor_social">
                    <button name="agendar" class="bg-green-600 text-white p-2 rounded">Agendar Labor Social</button>
                <?php elseif ($rol === 'Prácticas Profesionales'): ?>
                    <input type="hidden" name="tipo" value="pasantias">
                    <button name="agendar" class="bg-orange-600 text-white p-2 rounded">Agendar Pasantías</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Visualización de Reuniones (Privadas y aisladas) -->
        <div class="space-y-4">
            <?php 
            if ($rol === 'Profesor') {
                foreach(($_SESSION['reuniones_data']['profesores'][$usuario_actual] ?? []) as $r) 
                    echo "<div class='p-3 border-l-4 border-blue-500 bg-blue-50'>[{$r['seccion']}] {$r['titulo']} - {$r['fecha']}</div>";
            }
            if ($rol === 'Labor Social') {
                foreach(($_SESSION['reuniones_data']['labor_social'] ?? []) as $r) 
                    echo "<div class='p-3 border-l-4 border-green-500 bg-green-50'>{$r['titulo']} - {$r['fecha']}</div>";
            }
            if ($rol === 'Prácticas Profesionales') {
                foreach(($_SESSION['reuniones_data']['pasantias'] ?? []) as $r) 
                    echo "<div class='p-3 border-l-4 border-orange-500 bg-orange-50'>{$r['titulo']} - {$r['fecha']}</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
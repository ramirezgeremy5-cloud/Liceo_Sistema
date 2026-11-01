<?php
if (!isset($_SESSION['reuniones_data'])) {
    $_SESSION['reuniones_data'] = [
        'profesores' => [], // Formato: ['user_id' => [reuniones...]]
        'labor_social' => [], // Solo visible para Labor Social
        'pasantias' => [], // Solo visible para Pasantías
        'general' => [] // Visible para todos
    ];
}
?>
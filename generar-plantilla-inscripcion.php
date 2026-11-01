<?php
session_start();

if (!isset($_SESSION['usuario_logueado']) || !$_SESSION['usuario_logueado']) {
    header('Location: login.php');
    exit;
}

$nombre_institucion = "Escuela Técnica Pedro Garcia Leal";

// Enviar como HTML optimizado para impresión
// El usuario puede usar Ctrl+P o el navegador para guardar como PDF
header('Content-Type: text/html; charset=utf-8');
header('Pragma: public');
header('Cache-Control: public, must-revalidate');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Plantilla de Inscripción - <?php echo $nombre_institucion; ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            .page-break { page-break-after: always; }
        }
        body {
            margin: 20px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .print-header {
            text-align: center;
            padding: 10px;
            border-bottom: 3px solid #333;
            margin-bottom: 15px;
        }
        .print-header h1 {
            margin: 0;
            font-size: 18px;
        }
        .print-header p {
            margin: 5px 0 0 0;
            font-size: 11px;
        }
        .section-title {
            background-color: #e0e0e0;
            padding: 8px 10px;
            margin: 15px 0 10px 0;
            font-weight: bold;
            border-left: 5px solid #333;
        }
        .form-group {
            margin-bottom: 10px;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .form-group label {
            font-weight: bold;
            min-width: 150px;
            font-size: 11px;
        }
        .form-group .line {
            flex: 1;
            border-bottom: 1px solid #333;
            min-height: 20px;
            position: relative;
        }
        .authorization {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #333;
            background-color: #f5f5f5;
            font-size: 10px;
            line-height: 1.5;
        }
        .signature-section {
            margin-top: 30px;
            display: flex;
            gap: 60px;
        }
        .signature-block {
            flex: 1;
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #333;
            height: 60px;
            margin-bottom: 10px;
        }
        .sig-label {
            font-size: 10px;
            font-weight: bold;
        }
        .no-print {
            text-align: center;
            padding: 15px;
            background-color: #f0f0f0;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .no-print button {
            padding: 10px 20px;
            font-size: 14px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }
        .no-print button:hover {
            background-color: #2563eb;
        }
        .form-two-col {
            display: flex;
            gap: 30px;
        }
        .form-two-col > div {
            flex: 1;
        }
        .footer-note {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
        <button onclick="history.back()">← Volver</button>
        <p style="margin: 10px 0 0 0; font-size: 12px;">
            Haz clic en "Imprimir / Guardar como PDF" y selecciona "Guardar como PDF" en tu navegador.
        </p>
    </div>

    <div class="print-header">
        <h1><?php echo htmlspecialchars($nombre_institucion); ?></h1>
        <h2>PLANTILLA DE INSCRIPCIÓN DE REPRESENTANTE</h2>
        <p>Formulario para registro manual de datos</p>
    </div>

    <div class="section-title">DATOS DEL REPRESENTANTE</div>
    <div class="form-group">
        <label>Nombre Completo:</label>
        <div class="line"></div>
    </div>
    <div class="form-two-col">
        <div class="form-group">
            <label>Cédula/ID:</label>
            <div class="line"></div>
        </div>
        <div class="form-group">
            <label>Teléfono:</label>
            <div class="line"></div>
        </div>
    </div>
    <div class="form-group">
        <label>Correo Electrónico:</label>
        <div class="line"></div>
    </div>
    <div class="form-group">
        <label>Dirección:</label>
        <div class="line"></div>
    </div>
    <div class="form-group">
        <label>Ocupación:</label>
        <div class="line"></div>
    </div>

    <div class="section-title">DATOS DEL REPRESENTADO (ESTUDIANTE)</div>
    <div class="form-group">
        <label>Nombre Completo:</label>
        <div class="line"></div>
    </div>
    <div class="form-two-col">
        <div class="form-group">
            <label>Cédula:</label>
            <div class="line"></div>
        </div>
        <div class="form-group">
            <label>Fecha Nacimiento:</label>
            <div class="line"></div>
        </div>
    </div>
    <div class="form-two-col">
        <div class="form-group">
            <label>Nivel:</label>
            <div class="line"></div>
        </div>
        <div class="form-group">
            <label>Sección:</label>
            <div class="line"></div>
        </div>
    </div>
    <div class="form-two-col">
        <div class="form-group">
            <label>Género:</label>
            <div class="line"></div>
        </div>
        <div class="form-group">
            <label>Talla Uniforme:</label>
            <div class="line"></div>
        </div>
    </div>

    <div class="section-title">ANTECEDENTES DE SALUD</div>
    <div class="form-group">
        <label>Alergias:</label>
        <div class="line"></div>
    </div>
    <div class="form-group">
        <label>Medicamentos:</label>
        <div class="line"></div>
    </div>

    <div class="section-title">INFORMACIÓN DE EMERGENCIA</div>
    <div class="form-group">
        <label>Contacto Emergencia:</label>
        <div class="line"></div>
    </div>
    <div class="form-two-col">
        <div class="form-group">
            <label>Teléfono:</label>
            <div class="line"></div>
        </div>
        <div class="form-group">
            <label>Relación:</label>
            <div class="line"></div>
        </div>
    </div>

    <div class="authorization">
        <strong>AUTORIZACIÓN Y CONSENTIMIENTO</strong>
        <p style="margin-top: 8px;">
            Autorizo el acceso a la información académica y conductual de mi representado(a) a través del Sistema de Gestión Escolar de <?php echo htmlspecialchars($nombre_institucion); ?>. 
            He leído y acepto los términos de uso del sistema. Confirmo que la información proporcionada en este formulario es correcta y autorizo a la institución para contactarme 
            en caso de emergencia o para comunicar información importante sobre el desempeño académico y comportamiento del estudiante.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Firma del Representante</div>
        </div>
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Fecha (DD/MM/YYYY)</div>
        </div>
    </div>

    <div class="footer-note">
        <p>Formulario generado por el Sistema de Gestión Escolar el <?php echo date('d/m/Y H:i'); ?></p>
        <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($nombre_institucion); ?> - Todos los derechos reservados</p>
    </div>
</body>
</html>
<?php
exit;
?>

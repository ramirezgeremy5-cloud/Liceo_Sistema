# Sistema de Monitoreo de Convivencia y Conducta
## Bitácora Escolar con Firma Digital

### Descripción General
Este sistema permite a los profesores registrar en tiempo real la conducta de los estudiantes y a los representantes monitorear el comportamiento de sus hijos. Incluye un sistema de firma digital para amonestaciones graves que sirve como constancia legal.

---

## Para Profesores: Módulo "Reportes de Conducta"

### Acceso
- Menú principal → **Reportes de Conducta**
- URL: `localhost/liceo/modulos/reportes-conducta.php`

### Funcionalidades

#### 1. Crear Nuevo Reporte
- **Pestaña:** "Crear Reporte"
- **Campos:**
  - Estudiante (seleccionar de lista)
  - Sección (1ro A, 1ro B, 2do A, etc.)
  - Tipo de Reporte:
    - **Felicitación** (verde) - Comportamiento excepcional
    - **Llamado de Atención** (naranja) - Faltas menores
    - **Amonestación** (rojo) - Faltas graves
    - **Reporte Grave** (púrpura) - Muy grave, requiere firma
  - Fecha del incidente
  - Título/Asunto
  - Descripción detallada

#### 2. Historial de Reportes
- **Pestaña:** "Historial de Reportes"
- Ver todos los reportes creados
- Filtrar por sección
- Eliminar reportes si es necesario

### Características
- Reportes visibles inmediatamente para representantes
- Colores distintivos por tipo de incidencia
- Historial completo con timestamps
- Posibilidad de eliminar reportes registrados por error

---

## Para Representantes: Sección en Módulo "Representante"

### Acceso
- Menú principal → **Representante** → Pestaña **"Convivencia y Conducta"**

### Funcionalidades

#### 1. Ver Historial de Incidencias
- Todos los reportes cargados por profesores en tiempo real
- Organizados por fecha más reciente
- Código de colores:
  - 🟢 **Felicitación:** Comportamiento positivo
  - 🟡 **Llamado de Atención:** Falta menor
  - 🔴 **Amonestación:** Falta grave
  - 🟣 **Reporte Grave:** Muy grave

#### 2. Firma Digital de Amonestaciones
Para reportes de **"Amonestación"** y **"Reporte Grave"**:

- Se muestra un formulario con:
  - Campo para observaciones (opcional)
  - Botón **"✓ Leído y Conforme"**

- Al firmar, el sistema registra:
  - Fecha y hora exacta de lectura
  - IP del dispositivo
  - Navegador utilizado
  - Observaciones del representante
  - Constancia de firma digital

- Una vez firmado, muestra: **"✓ Firmado digitalmente como constancia de lectura"**

### Características
- Notificación inmediata de reportes graves
- Firma digital como constancia legal
- Imposible editar reportes ya firmados
- Historial completo para auditoría

---

## Tablas de Base de Datos

### reportes_conducta
```
- id: ID único del reporte
- id_estudiante: Identificador del estudiante
- seccion: Sección (1ro A, 1ro B, etc.)
- id_profesor: Profesor que crea el reporte
- tipo_reporte: Tipo de incidencia
- titulo: Asunto del reporte
- descripcion: Detalle completo
- fecha_reporte: Fecha del incidente
- hora_reporte: Hora del incidente
- fecha_creacion: Cuándo se registró
```

### firmas_amonestaciones
```
- id: ID único de la firma
- id_reporte: Reporte asociado
- id_representante: Representante que firma
- id_estudiante: Estudiante afectado
- confirmado: Si fue firmado
- fecha_lectura: Cuándo se leyó
- ip_direccion: IP del dispositivo
- navegador: Navegador utilizado
- observaciones: Notas del representante
- fecha_firma: Timestamp de la firma
```

---

## Tipos de Reportes

### 1. Felicitación (Verde)
- Excelente participación en clase
- Liderazgo académico
- Comportamiento ejemplar
- Ayuda a compañeros
- **No requiere firma**

### 2. Llamado de Atención (Naranja)
- Llegar tarde sin justificación
- Falta uniforme incompleto
- Interrupción en clase
- Tarea no realizada
- **No requiere firma**

### 3. Amonestación (Rojo)
- Inasistencia sin justificación
- Agresión verbal a compañero
- Uso de dispositivos en clase
- Intención de fraude
- **SÍ REQUIERE FIRMA del representante**

### 4. Reporte Grave (Púrpura)
- Agresión física
- Robo
- Conducta delictiva
- Acoso sexual
- **SÍ REQUIERE FIRMA URGENTE del representante**

---

## Flujo Operativo

### Paso 1: Profesor Registra Incidencia
```
Profesor crea reporte → Sistema registra inmediatamente
```

### Paso 2: Sistema Notifica Representante
```
Reporte visible en módulo Representante → Tiempo real
```

### Paso 3: Representante Lee y Firma
```
Para amonestaciones/graves:
Representante entra en tab "Convivencia y Conducta" 
→ Ve el reporte → Presiona "Leído y Conforme"
→ Se registra firma digital
```

### Paso 4: Constancia Legal
```
Firma digital = Prueba de que el representante fue notificado
Se guarda IP, hora exacta, navegador como evidencia
```

---

## Instrucciones de Uso

### Para Resetear Base de Datos
1. Accede a: `http://localhost/liceo/limpiar-bd.php`
2. Marca el checkbox de confirmación
3. Haz clic en "Limpiar Base de Datos"
4. Las tablas se recrearán automáticamente

### Credenciales de Prueba
- **Profesor:** usuario `profesor`, contraseña `profesor123`
- **Representante:** usuario `representante`, contraseña `representante123`

### Primer Uso
1. Entra como profesor
2. Ve a "Reportes de Conducta"
3. Crea un reporte de prueba
4. Entra como representante
5. Ve a "Representante" → "Convivencia y Conducta"
6. Verás el reporte en tiempo real
7. Si es amonestación, prueba la firma digital

---

## Consideraciones Legales

La firma digital en este sistema:
- Constituye constancia de lectura y conocimiento
- Prueba que el padre/representante fue notificado
- Se guarda con IP, fecha y hora exacta
- NO puede ser modificada una vez registrada
- Valida legalmente ante tribunales

---

## Solución de Problemas

### No veo los reportes
- Asegúrate de haber resetado la BD con `limpiar-bd.php`
- Verifica que estés logueado como representante
- Comprueba que hay reportes creados por un profesor

### La firma no funciona
- Asegúrate de que el reporte sea de tipo "Amonestación" o "Reporte Grave"
- Intenta refrescar la página
- Prueba con otro navegador

### No veo el botón "Convivencia y Conducta"
- Solo aparece si estás logueado como Representante
- Intenta limpiar el cache del navegador
- Reinicia sesión

---

## Ejemplo de Flujo Real

**Escenario:** Estudiante falta sin justificación

1. **Profesor registra:**
   - Tipo: Amonestación
   - Título: "Inasistencia sin justificación - 15 de Julio"
   - Descripción: "El estudiante no asistió a clase del 15/07/2026. No hay constancia de ausencia justificada. Requiere explicación."

2. **Sistema:**
   - Registra inmediatamente
   - Almacena en base de datos

3. **Representante ve:**
   - Una tarjeta roja con la amonestación
   - Botón "Leído y Conforme"
   - Formulario para observaciones

4. **Representante firma:**
   - Lee la amonestación
   - Agrega observación: "El estudiante se enfermó"
   - Presiona "Leído y Conforme"

5. **Sistema registra:**
   - Fecha: 16/07/2026 14:35:22
   - IP: 192.168.1.100
   - Navegador: Chrome 131.0
   - Observación registrada

6. **Prueba legal:**
   - Constancia de que el representante fue notificado
   - Firma digital comprobable
   - Evidencia para auditoría escolar

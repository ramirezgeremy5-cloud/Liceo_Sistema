<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Ayuda - Escuela Técnica Pedro Garcia Leal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #6b4423 0%, #8b5a2b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 700px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .chatbot-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.bot .message-content {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 4px solid #f59e0b;
        }

        .message.user .message-content {
            background: #6b4423;
            color: white;
        }

        .quick-replies {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .quick-reply-btn {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            text-align: left;
        }

        .quick-reply-btn:hover {
            background: #d97706;
            transform: translateX(5px);
        }

        .input-area {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            flex: 1;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .send-btn {
            background: #6b4423;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .send-btn:hover {
            background: #4a2f1a;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Centro de Ayuda</h1>
            <p>Escuela Técnica Pedro Garcia Leal</p>
        </div>

        <div class="chatbot-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    <div class="message-content">
                        Hola! Soy tu asistente de ayuda. Tengo respuestas a las preguntas más frecuentes sobre la instalación y funcionamiento del sistema. ¿En qué puedo ayudarte?
                    </div>
                </div>

                <div class="message bot">
                    <div class="message-content">
                        <div class="quick-replies">
                            <button class="quick-reply-btn" onclick="mostrarRespuesta(1)">1. ¿Cómo instalar el sistema?</button>
                            <button class="quick-reply-btn" onclick="mostrarRespuesta(2)">2. ¿Cómo funciona la seguridad y el login?</button>
                            <button class="quick-reply-btn" onclick="mostrarRespuesta(3)">3. ¿Cuáles son los requisitos del sistema?</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-area">
                <div class="input-group">
                    <input type="text" id="userInput" placeholder="Escribe tu pregunta..." onkeypress="if(event.key==='Enter') enviarMensaje()">
                    <button class="send-btn" onclick="enviarMensaje()">Enviar</button>
                </div>
            </div>
        </div>

        <div class="back-link">
            <a href="login.php">← Volver al Login</a>
        </div>
    </div>

    <script>
        const respuestas = {
            1: {
                pregunta: "¿Cómo instalar el sistema?",
                respuesta: `Para instalar el sistema sigue estos pasos:

1. DESCARGAR: Descarga el archivo ZIP desde v0
2. EXTRAER: Descomprime el ZIP en cualquier ubicación
3. COPIAR CARPETA: Busca la carpeta "liceo" dentro de los archivos extraidos
4. PEGAR: Copia la carpeta "liceo" a C:\\laragon\\www\\
5. INICIAR LARAGON: Abre Laragon y haz clic en "Start All"
6. ACCEDER: Abre tu navegador y ve a http://localhost/liceo/
7. LOGIN: Usa las credenciales de prueba

¡Listo! El sistema está instalado y funcionando.`
            },
            2: {
                pregunta: "¿Cómo funciona la seguridad y el login?",
                respuesta: `El sistema tiene 3 niveles de seguridad:

ADMINISTRADOR (admin/admin123)
- Acceso completo a todos los módulos
- Puede ver y gestionar todo el contenido
- Noticias, Pasantías, Labor Social, Representante

REPRESENTANTE (representante/rep123)
- Acceso solo para consultar (lectura)
- Puede ver: Noticias, Pasantías, Labor Social, Representante
- NO puede editar ni eliminar contenido

USUARIO (usuario/user123)
- Acceso limitado solo a Noticias
- Solo puede visualizar las noticias del liceo

Cada módulo valida el rol antes de permitir acceso.`
            },
            3: {
                pregunta: "¿Cuáles son los requisitos del sistema?",
                respuesta: `Requisitos técnicos:

SOFTWARE NECESARIO:
- Laragon 6.0+ (incluye Apache y PHP)
- Navegador web moderno (Chrome, Firefox, Edge)
- Windows, Mac o Linux

CARACTERÍSTICAS PRINCIPALES:
- 4 módulos: Noticias, Pasantías, Labor Social, Representante
- Sistema de seguridad con 3 roles
- Carga y descarga de archivos
- Diseño responsivo (móvil, tablet, pc)
- Colores institucionales: Amarillo y Marrón

NO REQUIERE:
- Base de datos
- Instalación en servidor remoto
- Configuración compleja

¡Es un sistema completamente funcional y listo para usar!`
            }
        };

        function mostrarRespuesta(numero) {
            const resp = respuestas[numero];
            
            // Agregar pregunta del usuario
            agregarMensaje(resp.pregunta, 'user');
            
            // Limpiar input
            document.getElementById('userInput').value = '';
            
            // Agregar respuesta del bot
            setTimeout(() => {
                agregarMensaje(resp.respuesta, 'bot');
            }, 500);
        }

        function enviarMensaje() {
            const input = document.getElementById('userInput');
            const texto = input.value.trim();
            
            if (!texto) return;
            
            agregarMensaje(texto, 'user');
            input.value = '';
            
            setTimeout(() => {
                const respuestaDefault = `Gracias por tu pregunta. Para respuestas específicas, usa los botones de arriba con las 3 preguntas frecuentes disponibles. Si necesitas más ayuda, intenta con:
                
1. ¿Cómo instalar el sistema?
2. ¿Cómo funciona la seguridad y el login?
3. ¿Cuáles son los requisitos del sistema?`;
                
                agregarMensaje(respuestaDefault, 'bot');
            }, 300);
        }

        function agregarMensaje(texto, tipo) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${tipo}`;
            messageDiv.innerHTML = `<div class="message-content">${texto}</div>`;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>

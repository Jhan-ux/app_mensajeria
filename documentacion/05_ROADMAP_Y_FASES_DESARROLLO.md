# 🚀 05. Roadmap y Fases de Desarrollo (Mensajería Anónima)

Este documento establece el plan de trabajo detallado por fases, con checklists de tareas para construir la aplicación de mensajería anónima paso a paso.

---

## 📌 Fase 1: Inicialización y Entorno de Datos
* [ ] Crear e inicializar el proyecto Laravel en `c:\xampp\htdocs\app_mensajeria`.
* [ ] Configurar variables de entorno `.env` (conexión a MySQL en XAMPP).
* [ ] Crear las migraciones adaptadas para usuarios anónimos (`username` único, sin email), `conversations`, `conversation_user`, `messages` y `message_receipts`.
* [ ] Crear los Modelos Eloquent (`User`, `Conversation`, `Message`, `MessageReceipt`) con sus relaciones.
* [ ] Crear Seeders y Factories con usuarios anónimos de prueba (`@user1`, `@user2`, etc.) y conversaciones de ejemplo.

---

## 📌 Fase 2: Autenticación Anónima y Búsqueda
* [ ] Configurar autenticación rápida por **`username` + `password`** (Laravel Sanctum).
* [ ] Generador de avatares predeterminados / anónimos para nuevos usuarios.
* [ ] Endpoint y vistas para actualización de apodo (`display_name`), avatar y mensaje de estado.
* [ ] Implementar buscador instantáneo por `@username`.
* [ ] Lógica para registrar última conexión (`last_seen_at`) y estado online/offline.

---

## 📌 Fase 3: Core de Conversaciones y Mensajería
* [ ] Servicio / Controlador para listar conversaciones con el último mensaje y contador de no leídos.
* [ ] Endpoint para iniciar chat directo buscando por `@username` (evitando duplicar conversaciones 1 a 1).
* [ ] Endpoint para crear grupos con múltiples `@usernames` y gestionar participantes.
* [ ] Endpoint para enviar mensajes de texto y guardar archivos adjuntos / notas de voz.
* [ ] Paginación de mensajes por cursor para carga infinita hacia arriba.
* [ ] Endpoint para marcar mensajes como leídos (doble check azul).

---

## 📌 Fase 4: Integración de Tiempo Real (Laravel Reverb & Echo)
* [ ] Instalar y configurar **Laravel Reverb** (`php artisan install:broadcasting`).
* [ ] Definir autorización de canales en `routes/channels.php`.
* [ ] Crear los eventos de Laravel (`MessageSent`, `MessageRead`, `UserTyping`, etc.).
* [ ] Configurar **Laravel Echo** en el frontend.
* [ ] Implementar presencia de usuarios (en línea / desconectado) y *"escribiendo..."*.

---

## 📌 Fase 5: Frontend UI/UX y Experiencia de Usuario
* [ ] Diseñar layout moderno tipo Telegram / Signal Web con tema oscuro y diseño minimalista.
* [ ] Pantalla de registro / login ultrarrápido (solo pide `@username` y contraseña).
* [ ] Panel lateral con lista de chats, buscador de usuarios por `@username` y perfil.
* [ ] Ventana de chat principal con cabecera de contacto, historial de mensajes y scroll automático.
* [ ] Entrada de chat con selector de emojis, botón de adjuntar archivos y grabador de notas de voz.
* [ ] Previsualización de imágenes y reproductor de audio integrado.
* [ ] Notificaciones sonoras y visuales al recibir mensajes nuevos.

---

## 📌 Fase 6: Pruebas, Seguridad y Optimización
* [ ] Pruebas de autenticación y validación de unicidad de `username`.
* [ ] Políticas de autorización (Policies de Laravel) para proteger conversaciones privadas.
* [ ] Optimización de consultas SQL (evitar problemas N+1 con eager loading).
* [ ] Compilación final de assets con Vite.

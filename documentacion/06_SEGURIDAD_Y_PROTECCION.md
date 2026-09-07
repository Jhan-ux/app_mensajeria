# 🛡️ Guía de Seguridad y Hardening del Sistema

Esta aplicación de mensajería anónima implementa un modelo de seguridad por capas (**Defensa en Profundidad**), garantizando tanto la privacidad estricta de la identidad como la integridad y confidencialidad de las comunicaciones.

---

## 1. Privacidad y Modelo de Identidad Anónima
- **Sin Datos Personales (PII):** No se solicita ni almacena correo electrónico, número de teléfono, nombre real ni geolocalización.
- **Identificadores Únicos:** Cada usuario se identifica únicamente mediante un `@username` único y un avatar generado proceduralmente.
- **Contraseñas Seguras:** Hasheadas utilizando Bcrypt con costo de trabajo adaptativo (`Hash::make`).

---

## 2. Cifrado de Datos en Reposo (AES-256)
- **Cifrado de Mensajes:** Los cuerpos de los mensajes de texto (`body`) se almacenan cifrados en la base de datos MySQL mediante el cast nativo de Eloquent `'body' => 'encrypted'`.
- **Protección contra Fugas:** Si la base de datos o sus respaldos SQL se ven comprometidos, los atacantes solo obtendrán cadenas cifradas con algoritmo AES-256-CBC mediante la clave de aplicación (`APP_KEY`).

---

## 3. Limitadores de Tasa (Anti-Fuerza Bruta y Anti-Spam)
Configurados en [`AppServiceProvider.php`](file:///c:/xampp/htdocs/app_mensajeria/app/Providers/AppServiceProvider.php) y aplicados en [`routes/web.php`](file:///c:/xampp/htdocs/app_mensajeria/routes/web.php):

| Endpoint / Acción | Límite de Tasa | Clave de Restricción | Propósito |
| :--- | :--- | :--- | :--- |
| `POST /login` | 5 intentos / minuto | `username` + IP | Bloqueo de ataques de diccionario y fuerza bruta |
| `POST /register` | 3 registros / minuto | Dirección IP | Previene la creación automatizada masiva de cuentas |
| `POST /api/conversations/{id}/messages` | 30 mensajes / minuto | Usuario autenticado o IP | Previene ataques de spam y flooding de canales |
| `GET /api/users/search` | 60 búsquedas / minuto | Usuario autenticado o IP | Previene scraping masivo de usuarios |

---

## 4. Cabeceras de Seguridad HTTP (Hardening Middleware)
Implementado a través de [`SecurityHeaders.php`](file:///c:/xampp/htdocs/app_mensajeria/app/Http/Middleware/SecurityHeaders.php) en el stack web:

- **`X-Frame-Options: SAMEORIGIN`**: Previene ataques de Clickjacking impidiendo que la aplicación sea embebida en iframes maliciosos.
- **`X-Content-Type-Options: nosniff`**: Impide que los navegadores interpreten archivos como tipos MIME diferentes al declarado.
- **`X-XSS-Protection: 1; mode=block`**: Activa el filtro anti-XSS integrado en clientes web.
- **`Referrer-Policy: strict-origin-when-cross-origin`**: Protege fugas de URL con tokens o identificadores al navegar a recursos externos.
- **`Permissions-Policy: camera=(), microphone=(self), geolocation=()`**: Restringe el acceso a periféricos, permitiendo el micrófono solo para la grabación de notas de voz en el propio origen.

---

## 5. Seguridad en Carga y Validación de Archivos
- **Lista Blanca de Extensiones (MIMEs):**
  - **Imágenes:** `jpg`, `jpeg`, `png`, `webp`, `gif`
  - **Audios / Notas de Voz:** `mp3`, `wav`, `ogg`, `webm`, `m4a`
  - **Documentos:** `pdf`, `doc`, `docx`, `txt`, `zip`
- **Bloqueo de Ejecutables:** Archivos peligrosos como `.php`, `.exe`, `.sh`, `.bat`, `.html`, `.js` son rechazados inmediatamente con error `422 Unprocessable Content`.
- **Límite de Tamaño:** Máximo 25 MB por archivo.
- **Sanitización de Nombres:** Nombres de archivo pasados por `basename()` para prevenir ataques de Directory Traversal (`../`).

---

## 6. Aislamiento y Control de Acceso (RBAC / Ownership)
- **Aislamiento de Conversaciones:** Un usuario no puede consultar, listar ni enviar mensajes en una conversación a la que no pertenece; la consulta falla de forma segura con `404 Not Found`.
- **Propiedad de Mensajes:** Solo el autor de un mensaje tiene autorización para marcarlo como eliminado.
- **Canales WebSocket Seguros:** Laravel Reverb y [`channels.php`](file:///c:/xampp/htdocs/app_mensajeria/routes/channels.php) validan la pertenencia del usuario a la conversación antes de autorizar la suscripción al canal privado.

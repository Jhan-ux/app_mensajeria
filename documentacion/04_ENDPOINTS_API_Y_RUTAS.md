# 🌐 04. Endpoints de la API y Rutas (Autenticación Anónima)

Este documento define la especificación de los endpoints HTTP/REST para la aplicación de mensajería anónima.

---

## 1. Módulo de Autenticación y Perfil Anónimo

### `POST /api/auth/register`
* **Descripción:** Registro anónimo e instantáneo con solo nombre de usuario único y contraseña.
* **Request Body:**
```json
{
  "username": "sombra_nocturna",
  "display_name": "Sombra",
  "password": "claveSegura123",
  "password_confirmation": "claveSegura123"
}
```
* **Response (201 Created):**
```json
{
  "message": "Usuario creado exitosamente",
  "token": "1|sanctum_token_string...",
  "user": {
    "id": 1,
    "username": "sombra_nocturna",
    "display_name": "Sombra",
    "avatar": "/defaults/avatar-1.svg",
    "status_message": "Disponible"
  }
}
```

---

### `POST /api/auth/login`
* **Descripción:** Inicio de sesión con `username` y `password`.
* **Request Body:**
```json
{
  "username": "sombra_nocturna",
  "password": "claveSegura123"
}
```
* **Response (200 OK):**
```json
{
  "token": "2|sanctum_token_string...",
  "user": {
    "id": 1,
    "username": "sombra_nocturna",
    "display_name": "Sombra",
    "avatar": "/defaults/avatar-1.svg",
    "status_message": "Disponible"
  }
}
```

---

### `POST /api/auth/logout`
* **Descripción:** Cierra la sesión activa del usuario y revoca el token o destruye la sesión.

---

### `GET /api/user/profile`
* **Descripción:** Obtiene los datos del usuario autenticado.

---

### `POST /api/user/profile` (Multipart Form)
* **Descripción:** Actualiza el apodo visual (`display_name`), avatar o mensaje de estado (`status_message`).

---

## 2. Módulo de Contactos y Búsqueda por `@username`

### `GET /api/users/search?username={query}`
* **Descripción:** Busca usuarios por su identificador exacto o coincidencia de `@username` para iniciar conversaciones.
* **Response (200 OK):**
```json
[
  {
    "id": 5,
    "username": "agente_007",
    "display_name": "James",
    "avatar": "/defaults/avatar-5.svg",
    "status_message": "En misión",
    "is_online": true
  }
]
```

---

## 3. Módulo de Conversaciones (Chats y Grupos)

### `GET /api/conversations`
* **Descripción:** Lista las conversaciones del usuario autenticado ordenadas por el último mensaje recibido, incluyendo número de mensajes sin leer.
* **Response (200 OK):**
```json
[
  {
    "id": 1,
    "type": "direct",
    "participant": {
      "id": 5,
      "username": "agente_007",
      "display_name": "James",
      "avatar": "/defaults/avatar-5.svg",
      "is_online": true
    },
    "last_message": {
      "id": 142,
      "body": "¿Revisaste el informe?",
      "type": "text",
      "created_at": "2026-09-07T14:30:00Z",
      "sender_id": 5
    },
    "unread_count": 2
  }
]
```

### `POST /api/conversations`
* **Descripción:** Crea una nueva conversación directa con otro usuario o un grupo.
* **Request Body (Chat Directo con otro username):**
```json
{
  "type": "direct",
  "username": "agente_007"
}
```
* **Request Body (Grupo):**
```json
{
  "type": "group",
  "title": "Cripto & Tech",
  "usernames": ["agente_007", "matrix_neo", "cypher"]
}
```

---

## 4. Módulo de Mensajes

### `GET /api/conversations/{id}/messages?cursor={cursor}&limit=30`
* **Descripción:** Obtiene los mensajes paginados de una conversación con paginación por cursor para scroll infinito fluido.
* **Response (200 OK):**
```json
{
  "data": [
    {
      "id": 142,
      "conversation_id": 1,
      "sender": {
        "id": 5,
        "username": "agente_007",
        "display_name": "James",
        "avatar": "/defaults/avatar-5.svg"
      },
      "type": "text",
      "body": "¿Revisaste el informe?",
      "file_path": null,
      "reply_to": null,
      "created_at": "2026-09-07T14:30:00Z",
      "status": "read"
    }
  ],
  "next_cursor": "eyJpZCI6MTQyfQ=="
}
```

### `POST /api/conversations/{id}/messages` (Multipart Form)
* **Descripción:** Envía un nuevo mensaje de texto o archivo multimedia.
* **Campos:**
  * `body`: (string, opcional si envía archivo)
  * `type`: `'text' | 'image' | 'audio' | 'document'`
  * `file`: (archivo binario, opcional)
  * `reply_to_id`: (id de mensaje citado, opcional)

### `POST /api/messages/{id}/read`
* **Descripción:** Marca un mensaje como leído y emite el evento WebSocket `MessageRead`.

### `DELETE /api/messages/{id}`
* **Descripción:** Marca el mensaje como eliminado (`is_deleted = true`).

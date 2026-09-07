# ⚡ 03. Eventos y WebSockets en Tiempo Real (Laravel Reverb)

Este documento detalla la configuración de WebSockets, canales de broadcasting y eventos en tiempo real para la mensajería instantánea.

---

## 1. Canales de Broadcasting (Canales de Difusión)

En Laravel, los canales se autorizan en `routes/channels.php`.

### A. Canal de Conversación (`presence-conversation.{conversationId}`)
* **Tipo:** Presencia (`Broadcast::presenceChannel`)
* **Propósito:**
  * Notificar qué usuarios están activos dentro de la sala de chat.
  * Transmitir eventos de *"Usuario está escribiendo..."* (`whisper` / eventos cliente).
  * Recibir nuevos mensajes enviados en esa conversación específica.
* **Autorización:** El usuario autenticado debe pertenecer a la conversación (`conversation_user`).

```php
Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId) {
    if ($user->conversations()->where('conversations.id', $conversationId)->exists()) {
        return ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatar];
    }
});
```

### B. Canal Personal del Usuario (`private-user.{userId}`)
* **Tipo:** Privado (`Broadcast::privateChannel`)
* **Propósito:**
  * Recibir notificaciones de nuevos mensajes cuando el usuario está en otra conversación o en otra pantalla.
  * Actualizaciones de conteo de mensajes no leídos (badge).
  * Invitaciones a nuevos grupos o chats.
* **Autorización:** `(int) $user->id === (int) $userId`.

---

## 2. Definición de Eventos en Laravel

| Evento | Canal | Payload de Datos | Cuándo se Dispara |
| :--- | :--- | :--- | :--- |
| `MessageSent` | `conversation.{id}` y `user.{recipient_id}` | Objeto `Message` completo, remitente, adjuntos, `reply_to` | Al guardar con éxito un nuevo mensaje. |
| `MessageRead` | `conversation.{id}` | `message_id`, `conversation_id`, `user_id`, `read_at` | Cuando el receptor abre o enfoca la conversación. |
| `UserTyping` | `conversation.{id}` | `user_id`, `user_name`, `is_typing` | Disparado desde el cliente (Whisper o evento temporal). |
| `UserPresenceChanged` | `presence-global` | `user_id`, `is_online`, `last_seen_at` | Al conectarse o desconectarse del WebSocket. |

---

## 3. Ejemplo de Clase de Evento: `MessageSent`

```php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender:id,name,avatar', 'replyTo']);
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
```

---

## 4. Consumo en Frontend con Laravel Echo

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Suscripción al canal de la conversación activa
const activeConversationId = 12;

window.Echo.join(`conversation.${activeConversationId}`)
    .here((users) => {
        console.log('Usuarios en el chat:', users);
    })
    .joining((user) => {
        console.log('Usuario conectado:', user.name);
    })
    .leaving((user) => {
        console.log('Usuario desconectado:', user.name);
    })
    .listen('.message.sent', (e) => {
        console.log('Nuevo mensaje recibido:', e.message);
        appendMessageToChatUI(e.message);
    })
    .listenForWhisper('typing', (e) => {
        showTypingIndicator(e.userName);
    });
```

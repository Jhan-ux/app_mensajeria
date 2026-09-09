<?php

namespace App\Http\Controllers;

use App\Events\MessagePinnedEvent;
use App\Events\MessageReacted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Allowed emoji reactions catalog.
     *
     * @var list<string>
     */
    public const ALLOWED_EMOJIS = ['👍', '❤️', '🔥', '😂', '🔒', '😮', '🎉', '👀'];

    /**
     * Display a paginated listing of messages for a conversation.
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $user->conversations()->findOrFail($conversationId);

        // Purge expired messages before fetching
        $this->purgeExpiredMessages($conversation);

        $limit = min((int) $request->input('limit', 40), 100);
        $beforeId = $request->input('before_id');

        $query = $conversation->messages()
            ->with([
                'sender:id,username,display_name,avatar,is_online',
                'replyTo.sender:id,username,display_name',
                'reactions.user:id,username,display_name',
                'pinnedBy:id,username,display_name',
            ])
            ->orderBy('id', 'desc');

        if ($beforeId) {
            $query->where('id', '<', (int) $beforeId);
        }

        $messages = $query->take($limit)->get()->reverse()->values();

        // Auto mark messages as read for this user
        if ($messages->isNotEmpty()) {
            $lastMsg = $messages->last();
            $this->markConversationAsRead($conversation, $user, $lastMsg->id);
        }

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $this->formatMessagePayload($m, $user)),
            'has_more' => $messages->count() >= $limit,
            'oldest_id' => $messages->first()?->id,
        ]);
    }

    /**
     * Store and broadcast a newly created message.
     */
    public function store(Request $request, int $conversationId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $user->conversations()->findOrFail($conversationId);

        $request->validate([
            'type' => ['nullable', 'in:text,image,audio,document'],
            'body' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:25600', 'mimes:jpg,jpeg,png,webp,gif,mp3,wav,ogg,webm,m4a,pdf,doc,docx,txt,zip'],
            'reply_to_id' => ['nullable', 'exists:messages,id'],
            'view_once' => ['nullable', 'boolean'],
        ]);

        $type = $request->input('type', 'text');
        $body = $request->input('body');
        $viewOnce = filter_var($request->input('view_once', false), FILTER_VALIDATE_BOOLEAN);
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $fileName = basename($file->getClientOriginalName());
            $fileSize = $file->getSize();

            if (str_starts_with($mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with($mime, 'audio/') || in_array($mime, ['video/webm', 'audio/webm', 'audio/ogg', 'audio/wav', 'audio/mpeg', 'audio/mp4', 'audio/x-m4a'])) {
                $type = 'audio';
            } else {
                $type = 'document';
            }

            $filePath = $file->store('attachments/'.$conversationId, 'public');
        }

        if (! $body && ! $filePath) {
            return response()->json(['message' => 'El mensaje no puede estar vacío'], 422);
        }

        // Ephemeral expiration timer
        $expiresAt = null;
        if ($conversation->ephemeral_timer && $conversation->ephemeral_timer > 0) {
            $expiresAt = now()->addSeconds((int) $conversation->ephemeral_timer);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'reply_to_id' => $request->input('reply_to_id'),
            'view_once' => $viewOnce,
            'expires_at' => $expiresAt,
        ]);

        $message->load([
            'sender:id,username,display_name,avatar,is_online',
            'replyTo.sender:id,username,display_name',
            'reactions.user:id,username,display_name',
        ]);

        // Broadcast real-time event via Laravel Reverb
        broadcast(new MessageSent($message))->toOthers();

        // Update sender's last read id
        $this->markConversationAsRead($conversation, $user, $message->id, false);

        return response()->json([
            'message' => $this->formatMessagePayload($message, $user),
        ], 201);
    }

    /**
     * Mark conversation as read by the user.
     */
    public function markAsRead(int $conversationId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $conversation = $user->conversations()->findOrFail($conversationId);

        $latestMessage = $conversation->messages()->latest('id')->first();
        if ($latestMessage) {
            $this->markConversationAsRead($conversation, $user, $latestMessage->id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle reaction with emoji on a message.
     */
    public function toggleReaction(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'emoji' => ['required', 'string', 'max:16'],
        ]);

        $emoji = trim($request->input('emoji'));

        /** @var Message $message */
        $message = Message::with('conversation.users')->findOrFail($id);

        if (! $message->conversation->users->contains('id', $user->id)) {
            return response()->json(['message' => 'No perteneces a esta conversación'], 403);
        }

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
            $action = 'added';
        }

        $summary = $this->buildReactionsSummary($message->fresh('reactions.user'), $user);

        // Broadcast event
        broadcast(new MessageReacted(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            userId: $user->id,
            emoji: $emoji,
            action: $action,
            reactionsSummary: $summary,
        ))->toOthers();

        return response()->json([
            'action' => $action,
            'reactions' => $summary,
        ]);
    }

    /**
     * Toggle pinned status on a message.
     */
    public function togglePin(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Message $message */
        $message = Message::with('conversation.users')->findOrFail($id);

        if (! $message->conversation->users->contains('id', $user->id)) {
            return response()->json(['message' => 'No perteneces a esta conversación'], 403);
        }

        $newPinned = ! $message->is_pinned;
        $message->update([
            'is_pinned' => $newPinned,
            'pinned_at' => $newPinned ? now() : null,
            'pinned_by' => $newPinned ? $user->id : null,
        ]);

        $messageData = $newPinned ? $this->formatMessagePayload($message->fresh(['sender', 'pinnedBy']), $user) : null;

        // Broadcast pin event
        broadcast(new MessagePinnedEvent(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            isPinned: $newPinned,
            messageData: $messageData,
        ))->toOthers();

        return response()->json([
            'is_pinned' => $newPinned,
            'message' => $messageData,
        ]);
    }

    /**
     * Consume a view-once multimedia message.
     */
    public function consumeViewOnce(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Message $message */
        $message = Message::with('conversation.users')->findOrFail($id);

        if (! $message->conversation->users->contains('id', $user->id)) {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        if (! $message->view_once) {
            return response()->json(['message' => 'Este mensaje no es de vista única'], 400);
        }

        if ($message->viewed_at !== null && $message->sender_id !== $user->id) {
            return response()->json([
                'message' => 'Este mensaje ya fue abierto y destruido',
                'is_destroyed' => true,
            ], 410);
        }

        // Marcar como visto
        if ($message->viewed_at === null) {
            $message->update(['viewed_at' => now()]);
        }

        return response()->json([
            'file_url' => $message->file_url,
            'file_name' => $message->file_name,
            'type' => $message->type,
            'body' => $message->body,
        ]);
    }

    /**
     * Delete a message (only sender).
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $message = Message::where('sender_id', $user->id)->findOrFail($id);
        $message->update([
            'is_deleted' => true,
            'is_pinned' => false,
            'pinned_at' => null,
            'pinned_by' => null,
        ]);

        return response()->json(['message' => 'Mensaje eliminado']);
    }

    /**
     * Helper to update read status and emit event.
     */
    private function markConversationAsRead(Conversation $conversation, User $user, int $lastMessageId, bool $shouldBroadcast = true): void
    {
        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_message_id' => $lastMessageId,
        ]);

        if ($shouldBroadcast) {
            broadcast(new MessageRead($conversation->id, $user->id, $lastMessageId))->toOthers();
        }
    }

    /**
     * Purge all expired messages and attached files in a conversation.
     */
    private function purgeExpiredMessages(Conversation $conversation): void
    {
        $expired = $conversation->messages()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $msg) {
            if ($msg->file_path && Storage::disk('public')->exists($msg->file_path)) {
                Storage::disk('public')->delete($msg->file_path);
            }
            $msg->delete();
        }
    }

    /**
     * Format a message for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatMessagePayload(Message $m, User $viewer): array
    {
        $isViewOnceDestroyed = $m->view_once && $m->viewed_at !== null && $m->sender_id !== $viewer->id;

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_id' => $m->sender_id,
            'is_me' => $m->sender_id === $viewer->id,
            'type' => $m->type,
            'body' => $m->is_deleted
                ? 'Este mensaje fue eliminado'
                : ($isViewOnceDestroyed ? 'Contenido de vista única destruido' : $m->body),
            'file_path' => $isViewOnceDestroyed ? null : $m->file_path,
            'file_name' => $isViewOnceDestroyed ? 'Contenido destruido' : $m->file_name,
            'file_size' => $isViewOnceDestroyed ? 0 : $m->file_size,
            'file_url' => $isViewOnceDestroyed ? null : $m->file_url,
            'is_deleted' => (bool) $m->is_deleted,
            'view_once' => (bool) $m->view_once,
            'is_viewed' => $m->viewed_at !== null,
            'expires_at' => $m->expires_at?->toIso8601String(),
            'is_pinned' => (bool) $m->is_pinned,
            'pinned_at' => $m->pinned_at?->toIso8601String(),
            'pinned_by' => $m->pinnedBy ? [
                'id' => $m->pinnedBy->id,
                'name' => $m->pinnedBy->name,
            ] : null,
            'reactions' => $this->buildReactionsSummary($m, $viewer),
            'reply_to' => $m->replyTo ? [
                'id' => $m->replyTo->id,
                'body' => $m->replyTo->is_deleted ? 'Mensaje eliminado' : $m->replyTo->body,
                'type' => $m->replyTo->type,
                'sender_name' => $m->replyTo->sender?->name,
            ] : null,
            'created_at' => $m->created_at->toIso8601String(),
            'sender' => [
                'id' => $m->sender->id,
                'username' => $m->sender->username,
                'name' => $m->sender->name,
                'avatar_url' => $m->sender->avatar_url,
            ],
        ];
    }

    /**
     * Build reactions summary grouped by emoji.
     *
     * @return list<array<string, mixed>>
     */
    private function buildReactionsSummary(Message $message, User $viewer): array
    {
        $reactions = $message->reactions ?? collect();

        return $reactions->groupBy('emoji')
            ->map(function ($group, $emoji) use ($viewer) {
                return [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'has_reacted' => $group->contains('user_id', $viewer->id),
                    'users' => $group->map(fn ($r) => $r->user?->name ?: 'Usuario')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}

<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a paginated listing of messages for a conversation.
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $user->conversations()->findOrFail($conversationId);

        $limit = min((int) $request->input('limit', 40), 100);
        $beforeId = $request->input('before_id');

        $query = $conversation->messages()
            ->with([
                'sender:id,username,display_name,avatar,is_online',
                'replyTo.sender:id,username,display_name',
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
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'conversation_id' => $m->conversation_id,
                'sender_id' => $m->sender_id,
                'is_me' => $m->sender_id === $user->id,
                'type' => $m->type,
                'body' => $m->is_deleted ? 'Este mensaje fue eliminado' : $m->body,
                'file_path' => $m->file_path,
                'file_name' => $m->file_name,
                'file_size' => $m->file_size,
                'file_url' => $m->file_url,
                'is_deleted' => (bool) $m->is_deleted,
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
            ]),
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
        ]);

        $type = $request->input('type', 'text');
        $body = $request->input('body');
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

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'reply_to_id' => $request->input('reply_to_id'),
        ]);

        // Broadcast real-time event via Laravel Reverb
        broadcast(new MessageSent($message))->toOthers();

        // Update sender's last read id
        $this->markConversationAsRead($conversation, $user, $message->id, false);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'is_me' => true,
                'type' => $message->type,
                'body' => $message->body,
                'file_path' => $message->file_path,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_url' => $message->file_url,
                'is_deleted' => false,
                'reply_to' => $message->replyTo,
                'created_at' => $message->created_at->toIso8601String(),
                'sender' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ],
            ],
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
     * Delete a message (only sender).
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $message = Message::where('sender_id', $user->id)->findOrFail($id);
        $message->update(['is_deleted' => true]);

        return response()->json(['message' => 'Mensaje eliminado']);
    }
}

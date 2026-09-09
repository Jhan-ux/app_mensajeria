<?php

namespace App\Http\Controllers;

use App\Events\ConversationUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations for the authenticated user.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with([
                'users:id,username,display_name,avatar,is_online,last_seen_at',
                'latestMessage.sender:id,username,display_name',
            ])
            ->withCount([
                'messages as pinned_count' => fn ($q) => $q->where('is_pinned', true),
            ])
            ->get()
            ->map(function (Conversation $conv) use ($user) {
                $otherUser = $conv->type === 'direct'
                    ? $conv->users->firstWhere('id', '!=', $user->id)
                    : null;

                return [
                    'id' => $conv->id,
                    'type' => $conv->type,
                    'title' => $conv->getTitleFor($user),
                    'avatar' => $conv->getAvatarFor($user),
                    'description' => $conv->description,
                    'ephemeral_timer' => (int) $conv->ephemeral_timer,
                    'pinned_count' => (int) $conv->pinned_count,
                    'is_online' => $otherUser ? (bool) $otherUser->is_online : false,
                    'last_seen_at' => $otherUser?->last_seen_at?->toIso8601String(),
                    'other_user' => $otherUser ? [
                        'id' => $otherUser->id,
                        'username' => $otherUser->username,
                        'name' => $otherUser->name,
                        'avatar_url' => $otherUser->avatar_url,
                        'status_message' => $otherUser->status_message,
                    ] : null,
                    'latest_message' => $conv->latestMessage ? [
                        'id' => $conv->latestMessage->id,
                        'sender_id' => $conv->latestMessage->sender_id,
                        'sender_name' => $conv->latestMessage->sender?->name,
                        'type' => $conv->latestMessage->type,
                        'body' => $conv->latestMessage->body,
                        'created_at' => $conv->latestMessage->created_at->toIso8601String(),
                    ] : null,
                    'unread_count' => $conv->unreadCountFor($user),
                    'updated_at' => $conv->latestMessage ? $conv->latestMessage->created_at : $conv->created_at,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    /**
     * Display the specified conversation.
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $user->conversations()
            ->with([
                'users:id,username,display_name,avatar,is_online,last_seen_at,status_message',
                'pinnedMessages' => fn ($q) => $q->with(['sender:id,username,display_name', 'pinnedBy:id,username,display_name']),
            ])
            ->findOrFail($id);

        $otherUser = $conversation->type === 'direct'
            ? $conversation->users->firstWhere('id', '!=', $user->id)
            : null;

        $userPivot = $conversation->users->firstWhere('id', $user->id)?->pivot;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->getTitleFor($user),
                'avatar' => $conversation->getAvatarFor($user),
                'description' => $conversation->description,
                'ephemeral_timer' => (int) $conversation->ephemeral_timer,
                'my_role' => $userPivot ? $userPivot->role : 'member',
                'is_online' => $otherUser ? (bool) $otherUser->is_online : false,
                'last_seen_at' => $otherUser?->last_seen_at?->toIso8601String(),
                'other_user' => $otherUser ? [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'name' => $otherUser->name,
                    'avatar_url' => $otherUser->avatar_url,
                    'status_message' => $otherUser->status_message,
                ] : null,
                'users' => $conversation->users->map(fn (User $u) => [
                    'id' => $u->id,
                    'username' => $u->username,
                    'name' => $u->name,
                    'avatar_url' => $u->avatar_url,
                    'status_message' => $u->status_message,
                    'is_online' => (bool) $u->is_online,
                    'role' => $u->pivot->role,
                ]),
                'pinned_messages' => $conversation->pinnedMessages->map(fn (Message $m) => [
                    'id' => $m->id,
                    'type' => $m->type,
                    'body' => $m->body,
                    'file_name' => $m->file_name,
                    'sender_name' => $m->sender?->name,
                    'pinned_at' => $m->pinned_at?->toIso8601String(),
                    'pinned_by_name' => $m->pinnedBy?->name,
                ]),
            ],
        ]);
    }

    /**
     * Search messages in a conversation.
     */
    public function searchMessages(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $user->conversations()->findOrFail($id);

        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['messages' => []]);
        }

        $allMessages = $conversation->messages()
            ->with('sender:id,username,display_name')
            ->where('is_deleted', false)
            ->latest('id')
            ->take(150)
            ->get();

        $lowerQuery = mb_strtolower($query);

        $filtered = $allMessages->filter(function (Message $msg) use ($lowerQuery) {
            if ($msg->view_once && $msg->viewed_at !== null) {
                return false;
            }

            $bodyMatch = $msg->body && str_contains(mb_strtolower($msg->body), $lowerQuery);
            $fileMatch = $msg->file_name && str_contains(mb_strtolower($msg->file_name), $lowerQuery);

            return $bodyMatch || $fileMatch;
        })->values();

        return response()->json([
            'query' => $query,
            'count' => $filtered->count(),
            'messages' => $filtered->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'type' => $m->type,
                'file_name' => $m->file_name,
                'created_at' => $m->created_at->toIso8601String(),
                'sender' => [
                    'id' => $m->sender->id,
                    'name' => $m->sender->name,
                ],
            ]),
        ]);
    }

    /**
     * Configure ephemeral messages self-destruct timer.
     */
    public function setEphemeralTimer(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'timer' => ['required', 'integer', 'in:0,60,300,3600,86400,604800'],
        ]);

        /** @var Conversation $conversation */
        $conversation = $user->conversations()->findOrFail($id);

        $timer = (int) $request->input('timer');
        $conversation->update([
            'ephemeral_timer' => $timer,
        ]);

        $recipientIds = $conversation->users->pluck('id')->all();

        broadcast(new ConversationUpdated(
            conversationId: $conversation->id,
            action: 'ephemeral_changed',
            data: ['ephemeral_timer' => $timer],
            recipientUserIds: $recipientIds
        ))->toOthers();

        return response()->json([
            'message' => 'Temporizador de mensajes efímeros actualizado',
            'ephemeral_timer' => $timer,
        ]);
    }

    /**
     * Add a member to a group conversation.
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $conversation = $currentUser->conversations()->findOrFail($id);

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Solo puedes añadir miembros a grupos'], 422);
        }

        $request->validate([
            'username' => ['required', 'string', 'max:50'],
        ]);

        $targetUsername = strtolower(trim(ltrim($request->input('username'), '@')));
        $targetUser = User::where('username', $targetUsername)->first();

        if (! $targetUser) {
            return response()->json(['message' => "El usuario '@{$targetUsername}' no existe"], 404);
        }

        if ($conversation->users->contains('id', $targetUser->id)) {
            return response()->json(['message' => 'El usuario ya es miembro de este grupo'], 422);
        }

        $conversation->users()->attach($targetUser->id, ['role' => 'member']);

        $recipientIds = $conversation->users()->pluck('users.id')->all();

        broadcast(new ConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_added',
            data: [
                'user' => [
                    'id' => $targetUser->id,
                    'username' => $targetUser->username,
                    'name' => $targetUser->name,
                    'avatar_url' => $targetUser->avatar_url,
                    'role' => 'member',
                ],
            ],
            recipientUserIds: $recipientIds
        ))->toOthers();

        return response()->json([
            'message' => "@{$targetUser->username} añadido al grupo",
            'user' => [
                'id' => $targetUser->id,
                'username' => $targetUser->username,
                'name' => $targetUser->name,
                'avatar_url' => $targetUser->avatar_url,
                'role' => 'member',
            ],
        ]);
    }

    /**
     * Remove / kick a member from a group (Admin only).
     */
    public function removeMember(int $id, int $userId): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $conversation = $currentUser->conversations()->findOrFail($id);

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Solo aplicable a grupos'], 422);
        }

        $myPivot = $conversation->users->firstWhere('id', $currentUser->id)?->pivot;
        if (! $myPivot || $myPivot->role !== 'admin') {
            return response()->json(['message' => 'Solo los administradores pueden expulsar miembros'], 403);
        }

        if ($userId === $currentUser->id) {
            return response()->json(['message' => 'Usa la opción salir del grupo para abandonarlo'], 422);
        }

        $conversation->users()->detach($userId);

        $recipientIds = $conversation->users()->pluck('users.id')->all();
        $recipientIds[] = $userId; // Notify the kicked user too

        broadcast(new ConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_removed',
            data: ['user_id' => $userId],
            recipientUserIds: $recipientIds
        ))->toOthers();

        return response()->json(['message' => 'Miembro expulsado del grupo']);
    }

    /**
     * Leave a group conversation.
     */
    public function leaveGroup(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $conversation = $currentUser->conversations()->findOrFail($id);

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Solo aplicable a grupos'], 422);
        }

        $myPivot = $conversation->users->firstWhere('id', $currentUser->id)?->pivot;
        $wasAdmin = $myPivot && $myPivot->role === 'admin';

        $conversation->users()->detach($currentUser->id);

        // If no members left, remove conversation
        $remainingCount = $conversation->users()->count();
        if ($remainingCount === 0) {
            $conversation->delete();

            return response()->json(['message' => 'Has abandonado y cerrado el grupo']);
        }

        // If was admin, promote next member if no admins remain
        if ($wasAdmin) {
            $hasOtherAdmin = $conversation->users()->wherePivot('role', 'admin')->exists();
            if (! $hasOtherAdmin) {
                $nextMember = $conversation->users()->first();
                if ($nextMember) {
                    $conversation->users()->updateExistingPivot($nextMember->id, ['role' => 'admin']);
                }
            }
        }

        $recipientIds = $conversation->users()->pluck('users.id')->all();

        broadcast(new ConversationUpdated(
            conversationId: $conversation->id,
            action: 'member_left',
            data: ['user_id' => $currentUser->id],
            recipientUserIds: $recipientIds
        ))->toOthers();

        return response()->json(['message' => 'Has salido del grupo']);
    }

    /**
     * Update group info (title, description, avatar).
     */
    public function updateInfo(Request $request, int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $conversation = $currentUser->conversations()->findOrFail($id);

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Solo aplicable a grupos'], 422);
        }

        $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'url', 'max:1000'],
            'avatar_file' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->filled('title')) {
            $conversation->title = trim($request->input('title'));
        }

        if ($request->has('description')) {
            $conversation->description = trim((string) $request->input('description'));
        }

        if ($request->filled('avatar_url')) {
            $conversation->avatar = trim($request->input('avatar_url'));
        } elseif ($request->hasFile('avatar_file')) {
            $path = $request->file('avatar_file')->store('group-avatars', 'public');
            $conversation->avatar = $path;
        }

        $conversation->save();

        $recipientIds = $conversation->users()->pluck('users.id')->all();

        broadcast(new ConversationUpdated(
            conversationId: $conversation->id,
            action: 'info_updated',
            data: [
                'title' => $conversation->title,
                'description' => $conversation->description,
                'avatar' => $conversation->getAvatarFor($currentUser),
            ],
            recipientUserIds: $recipientIds
        ))->toOthers();

        return response()->json([
            'message' => 'Información del grupo actualizada',
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'description' => $conversation->description,
                'avatar' => $conversation->getAvatarFor($currentUser),
            ],
        ]);
    }

    /**
     * Store a new direct or group conversation.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'username' => ['required_if:type,direct', 'string', 'nullable'],
            'title' => ['required_if:type,group', 'string', 'max:100', 'nullable'],
            'description' => ['nullable', 'string', 'max:255'],
            'usernames' => ['required_if:type,group', 'array', 'nullable'],
            'usernames.*' => ['string'],
        ]);

        if ($data['type'] === 'direct') {
            $targetUsername = strtolower(trim($data['username']));

            if ($targetUsername === strtolower($currentUser->username)) {
                return response()->json(['message' => 'No puedes iniciar un chat directo contigo mismo'], 422);
            }

            $targetUser = User::where('username', $targetUsername)->first();
            if (! $targetUser) {
                return response()->json(['message' => "El usuario '@{$targetUsername}' no existe"], 404);
            }

            // Check if direct conversation already exists
            $existing = Conversation::where('type', 'direct')
                ->whereHas('users', fn ($q) => $q->where('users.id', $currentUser->id))
                ->whereHas('users', fn ($q) => $q->where('users.id', $targetUser->id))
                ->first();

            if ($existing) {
                return response()->json([
                    'conversation_id' => $existing->id,
                    'is_new' => false,
                ]);
            }

            $conversation = DB::transaction(function () use ($currentUser, $targetUser) {
                $conv = Conversation::create([
                    'type' => 'direct',
                    'created_by' => $currentUser->id,
                ]);

                $conv->users()->attach([
                    $currentUser->id => ['role' => 'member'],
                    $targetUser->id => ['role' => 'member'],
                ]);

                return $conv;
            });

            return response()->json([
                'conversation_id' => $conversation->id,
                'is_new' => true,
            ], 201);
        }

        // Group conversation
        $targetUsernames = collect($data['usernames'] ?? [])
            ->map(fn ($u) => strtolower(trim($u)))
            ->unique()
            ->filter(fn ($u) => $u !== strtolower($currentUser->username));

        $foundUsers = User::whereIn('username', $targetUsernames)->get();

        $conversation = DB::transaction(function () use ($currentUser, $data, $foundUsers) {
            $conv = Conversation::create([
                'type' => 'group',
                'title' => trim($data['title']),
                'description' => ! empty($data['description']) ? trim($data['description']) : null,
                'avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed='.urlencode($data['title']),
                'created_by' => $currentUser->id,
            ]);

            $attachData = [$currentUser->id => ['role' => 'admin']];
            foreach ($foundUsers as $u) {
                $attachData[$u->id] = ['role' => 'member'];
            }

            $conv->users()->attach($attachData);

            return $conv;
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'is_new' => true,
        ], 201);
    }
}

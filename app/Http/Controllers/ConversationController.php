<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
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
            ->with('users:id,username,display_name,avatar,is_online,last_seen_at,status_message')
            ->findOrFail($id);

        $otherUser = $conversation->type === 'direct'
            ? $conversation->users->firstWhere('id', '!=', $user->id)
            : null;

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->getTitleFor($user),
                'avatar' => $conversation->getAvatarFor($user),
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
                    'role' => $u->pivot->role,
                ]),
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

<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId) {
    if ($user->conversations()->where('conversations.id', $conversationId)->exists()) {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ];
    }

    return false;
});

Broadcast::channel('user.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('online-users', function (User $user) {
    return [
        'id' => $user->id,
        'username' => $user->username,
        'name' => $user->name,
        'avatar_url' => $user->avatar_url,
    ];
});

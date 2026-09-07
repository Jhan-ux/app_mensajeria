<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Search users by username or display name for starting chats.
     */
    public function search(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = strtolower(trim($request->input('q', $request->input('username', ''))));
        $query = ltrim($query, '@');

        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where('id', '!=', $currentUser->id)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                    ->orWhere('display_name', 'like', "%{$query}%");
            })
            ->take(20)
            ->get(['id', 'username', 'display_name', 'avatar', 'status_message', 'is_online', 'last_seen_at']);

        return response()->json([
            'users' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'username' => $u->username,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'status_message' => $u->status_message,
                'is_online' => (bool) $u->is_online,
            ]),
        ]);
    }

    /**
     * Update current user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'display_name' => ['nullable', 'string', 'max:50'],
            'status_message' => ['nullable', 'string', 'max:150'],
            'avatar_url' => ['nullable', 'url', 'max:1000'],
            'avatar_file' => ['nullable', 'image', 'max:5120'], // 5MB max
            'avatar_seed' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->has('display_name')) {
            $user->display_name = $request->input('display_name') ?: $user->username;
        }

        if ($request->has('status_message')) {
            $user->status_message = $request->input('status_message');
        }

        if ($request->filled('avatar_url')) {
            $user->avatar = trim($request->input('avatar_url'));
        } elseif ($request->hasFile('avatar_file')) {
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->avatar = $path;
        } elseif ($request->filled('avatar_seed')) {
            $user->avatar = 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed='.urlencode($request->input('avatar_seed'));
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil anónimo actualizado',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'status_message' => $user->status_message,
            ],
        ]);
    }

    /**
     * Heartbeat / update online status.
     */
    public function heartbeat(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        return response()->json(['status' => 'online']);
    }
}

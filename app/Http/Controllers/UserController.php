<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
     * Update security recovery questions for authenticated user.
     */
    public function updateSecurityQuestions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'security_question_1' => ['required', 'string', 'max:255'],
            'security_answer_1' => ['required', 'string', 'min:2', 'max:255'],
            'security_question_2' => ['required', 'string', 'max:255', 'different:security_question_1'],
            'security_answer_2' => ['required', 'string', 'min:2', 'max:255'],
        ], [
            'security_question_2.different' => 'Debes elegir dos preguntas de seguridad distintas.',
            'security_answer_1.required' => 'Debes ingresar una respuesta para la primera pregunta.',
            'security_answer_2.required' => 'Debes ingresar una respuesta para la segunda pregunta.',
            'security_answer_1.min' => 'La respuesta 1 debe tener al menos 2 caracteres.',
            'security_answer_2.min' => 'La respuesta 2 debe tener al menos 2 caracteres.',
        ]);

        $user->update([
            'security_question_1' => $data['security_question_1'],
            'security_answer_1' => Hash::make(mb_strtolower(trim($data['security_answer_1']))),
            'security_question_2' => $data['security_question_2'],
            'security_answer_2' => Hash::make(mb_strtolower(trim($data['security_answer_2']))),
        ]);

        return response()->json([
            'message' => 'Preguntas de seguridad configuradas exitosamente',
            'user' => $user->fresh(),
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

    /**
     * Danger Zone: Permanently destroy user account and all trace of data.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta. Purga cancelada por seguridad.'], 422);
        }

        $userId = $user->id;
        $userAvatar = $user->avatar;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($userId, $userAvatar) {
            // 1. Delete user files / attachments from storage
            $messages = Message::where('sender_id', $userId)->whereNotNull('file_path')->get();
            foreach ($messages as $msg) {
                if ($msg->file_path && Storage::disk('public')->exists($msg->file_path)) {
                    Storage::disk('public')->delete($msg->file_path);
                }
            }

            // 2. Delete avatar if local
            if ($userAvatar && ! str_starts_with($userAvatar, 'http') && Storage::disk('public')->exists($userAvatar)) {
                Storage::disk('public')->delete($userAvatar);
            }

            // 3. Delete direct conversations where user is participant
            $directConvIds = DB::table('conversation_user')
                ->join('conversations', 'conversations.id', '=', 'conversation_user.conversation_id')
                ->where('conversation_user.user_id', $userId)
                ->where('conversations.type', 'direct')
                ->pluck('conversations.id');

            foreach ($directConvIds as $convId) {
                $convMessages = Message::where('conversation_id', $convId)->whereNotNull('file_path')->get();
                foreach ($convMessages as $m) {
                    if ($m->file_path && Storage::disk('public')->exists($m->file_path)) {
                        Storage::disk('public')->delete($m->file_path);
                    }
                }
                Conversation::where('id', $convId)->delete();
            }

            // 4. Detach from groups and clean empty groups
            $groupConvIds = DB::table('conversation_user')
                ->join('conversations', 'conversations.id', '=', 'conversation_user.conversation_id')
                ->where('conversation_user.user_id', $userId)
                ->where('conversations.type', 'group')
                ->pluck('conversations.id');

            foreach ($groupConvIds as $groupId) {
                DB::table('conversation_user')
                    ->where('conversation_id', $groupId)
                    ->where('user_id', $userId)
                    ->delete();

                if (DB::table('conversation_user')->where('conversation_id', $groupId)->count() === 0) {
                    Conversation::where('id', $groupId)->delete();
                }
            }

            // 5. Delete user record
            User::where('id', $userId)->delete();
        });

        return response()->json([
            'message' => 'Tu cuenta y todos sus datos han sido purgados y destruidos permanentemente.',
            'redirect' => route('login'),
        ]);
    }
}

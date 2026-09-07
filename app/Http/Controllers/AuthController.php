<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login / register view.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }

        return view('auth.login');
    }

    /**
     * Authenticate user with username and password.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $username = strtolower(trim($credentials['username']));

        if (! Auth::attempt(['username' => $username, 'password' => $credentials['password']], true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Credenciales inválidas. Verifica tu nombre de usuario y contraseña.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'username' => ['Credenciales inválidas. Verifica tu nombre de usuario y contraseña.'],
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Bienvenido de nuevo',
                'user' => $user,
            ]);
        }

        return redirect()->intended(route('chat.index'));
    }

    /**
     * Register a new anonymous user.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'display_name' => ['nullable', 'string', 'max:50'],
            'avatar_url' => ['nullable', 'url', 'max:1000'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'username.regex' => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'username.unique' => 'Este nombre de usuario ya está en uso. Elige otro para mantener tu anonimato.',
            'avatar_url.url' => 'El enlace de la foto de perfil debe ser una URL válida (ej: https://ejemplo.com/foto.jpg).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $username = strtolower(trim($data['username']));
        $avatar = ! empty($data['avatar_url']) ? trim($data['avatar_url']) : 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed='.urlencode($username);

        $user = User::create([
            'username' => $username,
            'display_name' => $data['display_name'] ?: $username,
            'password' => Hash::make($data['password']),
            'avatar' => $avatar,
            'status_message' => 'Disponible en canal anónimo',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cuenta anónima creada exitosamente',
                'user' => $user,
            ], 201);
        }

        return redirect()->route('chat.index');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Sesión cerrada']);
        }

        return redirect()->route('login');
    }

    /**
     * Get current authenticated user profile.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::user(),
        ]);
    }
}

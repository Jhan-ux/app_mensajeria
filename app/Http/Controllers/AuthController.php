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
     * Catálogo oficial de preguntas de seguridad para recuperación anónima.
     *
     * @var list<string>
     */
    public const SECURITY_QUESTIONS = [
        '¿Cuál es el nombre de tu primera mascota?',
        '¿En qué ciudad o pueblo naciste?',
        '¿Cuál es tu película, libro o serie favorita?',
        '¿Cuál era tu apodo o alias de la infancia?',
        '¿Cuál es el nombre de tu escuela primaria?',
        '¿Cuál es tu comida, postre o plato favorito?',
        '¿Cuál fue tu primer videojuego o consola?',
        '¿Cuál es el segundo nombre de tu madre o abuela?',
    ];

    /**
     * Show login / register view.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('chat.index');
        }

        return view('auth.login', [
            'securityQuestions' => self::SECURITY_QUESTIONS,
        ]);
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
            'security_question_1' => ['nullable', 'string', 'max:255'],
            'security_answer_1' => ['nullable', 'string', 'min:2', 'max:255', 'required_with:security_question_1'],
            'security_question_2' => ['nullable', 'string', 'max:255', 'different:security_question_1'],
            'security_answer_2' => ['nullable', 'string', 'min:2', 'max:255', 'required_with:security_question_2'],
        ], [
            'username.regex' => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'username.unique' => 'Este nombre de usuario ya está en uso. Elige otro para mantener tu anonimato.',
            'avatar_url.url' => 'El enlace de la foto de perfil debe ser una URL válida (ej: https://ejemplo.com/foto.jpg).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'security_question_2.different' => 'Debes elegir dos preguntas de seguridad distintas.',
            'security_answer_1.required_with' => 'Debes ingresar una respuesta para la primera pregunta de seguridad.',
            'security_answer_2.required_with' => 'Debes ingresar una respuesta para la segunda pregunta de seguridad.',
            'security_answer_1.min' => 'La respuesta a la pregunta 1 debe tener al menos 2 caracteres.',
            'security_answer_2.min' => 'La respuesta a la pregunta 2 debe tener al menos 2 caracteres.',
        ]);

        $username = strtolower(trim($data['username']));
        $avatar = ! empty($data['avatar_url']) ? trim($data['avatar_url']) : 'https://api.dicebear.com/7.x/bottts-neutral/svg?seed='.urlencode($username);

        $user = User::create([
            'username' => $username,
            'display_name' => $data['display_name'] ?: $username,
            'password' => Hash::make($data['password']),
            'avatar' => $avatar,
            'status_message' => 'Disponible en canal anónimo',
            'security_question_1' => $data['security_question_1'] ?? null,
            'security_answer_1' => ! empty($data['security_answer_1']) ? Hash::make(mb_strtolower(trim($data['security_answer_1']))) : null,
            'security_question_2' => $data['security_question_2'] ?? null,
            'security_answer_2' => ! empty($data['security_answer_2']) ? Hash::make(mb_strtolower(trim($data['security_answer_2']))) : null,
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
     * Fetch the 2 security questions for a given username.
     */
    public function getRecoveryQuestions(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:50'],
        ]);

        $username = strtolower(trim($request->username));
        $user = User::where('username', $username)->first();

        if (! $user || empty($user->security_question_1) || empty($user->security_question_2)) {
            return response()->json([
                'message' => 'No se encontraron preguntas de seguridad configuradas para este usuario o la cuenta no existe.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'username' => $user->username,
            'question_1' => $user->security_question_1,
            'question_2' => $user->security_question_2,
        ]);
    }

    /**
     * Verify the 2 security answers and reset password.
     */
    public function resetPasswordWithSecurityQuestions(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'answer_1' => ['required', 'string', 'min:2', 'max:255'],
            'answer_2' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'Las nuevas contraseñas no coinciden.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'answer_1.required' => 'Debes responder la primera pregunta de seguridad.',
            'answer_2.required' => 'Debes responder la segunda pregunta de seguridad.',
        ]);

        $username = strtolower(trim($data['username']));
        $user = User::where('username', $username)->first();

        if (! $user || empty($user->security_answer_1) || empty($user->security_answer_2)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'No es posible recuperar la cuenta solicitada.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'username' => ['No es posible recuperar la cuenta solicitada.'],
            ]);
        }

        $valid1 = Hash::check(mb_strtolower(trim($data['answer_1'])), $user->security_answer_1);
        $valid2 = Hash::check(mb_strtolower(trim($data['answer_2'])), $user->security_answer_2);

        if (! $valid1 || ! $valid2) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Una o ambas respuestas de seguridad son incorrectas.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'answer_1' => ['Una o ambas respuestas de seguridad son incorrectas.'],
            ]);
        }

        // Actualizar contraseña
        $user->update([
            'password' => Hash::make($data['password']),
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Contraseña restablecida exitosamente. Bienvenido a tu canal seguro.',
                'user' => $user,
            ]);
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

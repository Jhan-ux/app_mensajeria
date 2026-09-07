<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Protección contra ataques de fuerza bruta en Login (5 intentos/minuto por usuario + IP)
        RateLimiter::for('login', function (Request $request) {
            $username = Str::transliterate(Str::lower($request->input('username', '')));

            return Limit::perMinute(5)->by($username.'|'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Demasiados intentos de acceso fallidos. Por favor, espera 1 minuto antes de volver a intentarlo.',
                ], 429);
            });
        });

        // 2. Protección contra creación masiva de cuentas (3 cuentas/minuto por IP)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Límite de creación de cuentas alcanzado. Por favor, espera 1 minuto.',
                ], 429);
            });
        });

        // 3. Protección contra flood/spam de mensajes (30 mensajes/minuto por usuario)
        RateLimiter::for('messages', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'message' => 'Estás enviando mensajes demasiado rápido. Por favor, espera un momento.',
                ], 429);
            });
        });

        // 4. Rate limiter para búsquedas de contactos
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 5. Protección contra fuerza bruta en recuperación de cuenta (5 intentos/minuto por usuario + IP)
        RateLimiter::for('recovery', function (Request $request) {
            $username = Str::transliterate(Str::lower($request->input('username', '')));

            return Limit::perMinute(5)->by($username.'|'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Demasiados intentos de recuperación. Por favor, espera 1 minuto.',
                ], 429);
            });
        });
    }
}

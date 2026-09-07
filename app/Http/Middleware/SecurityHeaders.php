<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach hardening security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevenir ataques de clickjacking (embebido en iframes)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevenir ataques de MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Activar filtro anti-XSS en navegadores legados
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Política de Referrer para no filtrar URLs con tokens
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Política de permisos de hardware (restringir micrófono a self para notas de voz)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()');

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class KioskGuard
{
    /**
     * Deja pasar a las tablets del kiosco y al personal autenticado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $token = config('kiosk.token');

        if (blank($token)) {
            Log::warning('Kiosk access denied: KIOSK_TOKEN no está configurado.');

            abort(403, 'El kiosco no está configurado.');
        }

        $cookieName = config('kiosk.cookie');

        if (is_string($request->cookie($cookieName)) && hash_equals($token, $request->cookie($cookieName))) {
            return $next($request);
        }

        $provided = $request->query('kiosk_token');

        if (is_string($provided) && hash_equals($token, $provided)) {
            // Autoriza esta tablet y quita el token de la URL para que no
            // quede en el historial ni en los enlaces internos.
            return redirect($request->url())
                ->withCookie(Cookie::forever($cookieName, $token));
        }

        abort(403, 'Esta pantalla solo está disponible en las tablets del kiosco.');
    }
}

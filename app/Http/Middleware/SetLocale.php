<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale the visitor picked (stored in the session by
 * LocaleController) to every web request, falling back to the app default.
 */
class SetLocale
{
    public const SESSION_KEY = 'onboarding_locale';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY);

        if (is_string($locale) && in_array($locale, config('onboarding.locales', []), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

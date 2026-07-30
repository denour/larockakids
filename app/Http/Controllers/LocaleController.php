<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Store the requested locale in the session and bounce the visitor back to
     * the screen they came from. Unknown locales never reach here: the route
     * constrains {locale} to the whitelist in config('onboarding.locales').
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        return back();
    }
}

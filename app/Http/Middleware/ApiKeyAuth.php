<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $plainToken = $request->header('X-API-Key') ?? $request->bearerToken() ?? $request->query('api_key');
        
        if (!$plainToken) {
            return response()->json(['error' => 'No API token provided'], 401);
        }

        $token = ApiToken::findByPlainToken($plainToken);

        if (!$token) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        if ($token->isExpired()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        if ($ability && !$token->hasAbility($ability)) {
            return response()->json(['error' => 'Token lacks required ability: ' . $ability], 403);
        }

        $token->markAsUsed();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}

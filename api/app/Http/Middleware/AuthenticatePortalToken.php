<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePortalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        if (!$plainToken) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        $token = ApiAccessToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (!$token || $token->expires_at->isPast() || !$token->user || !$token->user->is_active) {
            if ($token) $token->delete();
            return response()->json(['message' => 'Your session has expired. Please sign in again.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('portal_user', $token->user);
        $request->attributes->set('portal_token', $token);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}

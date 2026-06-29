<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureServiceToken
{
    /**
     * Authenticate requests from the Node.js chat server.
     * Uses a long-lived Sanctum token stored in NODE_SERVICE_TOKEN env var.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token   = $request->bearerToken();
        $expected = config('services.node_service_token');

        if (!$token || !$expected || !hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return $next($request);
    }

    /** Fluent alias: EnsureRole::using('client') */
    public static function using(string ...$roles): string
    {
        return static::class . ':' . implode(',', $roles);
    }
}

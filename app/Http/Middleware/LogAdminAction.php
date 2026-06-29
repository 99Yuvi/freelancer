<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;

class LogAdminAction
{
    private static array $SKIP = ['GET', 'HEAD'];

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Only log mutating actions on successful responses
        if (
            !in_array($request->method(), self::$SKIP)
            && $response->getStatusCode() < 400
            && $request->user()
        ) {
            AuditLog::record(
                $request->user(),
                $request->method() . ' ' . $request->path(),
                null,
                $request->except(['password', 'password_confirmation'])
            );
        }

        return $response;
    }
}

<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie auth — stateful domains from config
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
                'api/*',        
                'api',   
            ]);
        // Register custom alias
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // API-only backend — never redirect to a login page, always return 401
        $middleware->redirectGuestsTo(fn() => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for API routes on any unhandled exception
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'message' => $status === 500
                        ? 'Server error. Our team has been notified.'
                        : $e->getMessage(),
                ], $status);
            }
        });
    })->create();

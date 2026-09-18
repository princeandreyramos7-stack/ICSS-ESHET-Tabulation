<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Production is typically behind a reverse proxy (hosting panel, Cloudflare, load balancer).
        // Trusting it lets Laravel see the real scheme so generated links are https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Friendly, branded error pages through Inertia. Debug mode keeps the
        // full Laravel error page for developers.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419) {
                // Expired CSRF token / session: send them back with a toast instead of a dead page.
                return back()->with('error', 'Your session expired. Please try again.');
            }

            $friendly = [401, 403, 404, 429, 500, 503];
            if (in_array($status, $friendly, true) && ! (config('app.debug') && $status >= 500)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();

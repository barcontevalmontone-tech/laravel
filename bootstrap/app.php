<?php

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
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AssicuraAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Violazioni del regolamento causali: risposta JSON 422 per l'API,
        // messaggio d'errore in sessione per il pannello web.
        $exceptions->render(function (\App\Exceptions\CausaleNonConsentita $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        // Hook di reporting centralizzato: punto d'ingresso per future
        // integrazioni esterne (es. Sentry) senza disperdere la logica.
        $exceptions->reportable(function (Throwable $e) {
            //
        });
    })->create();

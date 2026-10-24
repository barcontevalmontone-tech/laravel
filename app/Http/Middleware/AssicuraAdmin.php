<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consente l'accesso solo agli utenti con ruolo "admin".
 */
class AssicuraAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $utente = $request->user();

        if ($utente === null || ! $utente->isAdmin()) {
            abort(403, 'Accesso riservato agli amministratori.');
        }

        return $next($request);
    }
}

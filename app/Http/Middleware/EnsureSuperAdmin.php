<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint l'accès aux utilisateurs authentifiés ayant le rôle super_admin (Shield).
 */
class EnsureSuperAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise.'], 401);
            }

            return redirect()->guest(url('/admin/login'));
        }

        $superAdminRole = (string) config('filament-shield.super_admin.name', 'super_admin');

        if (! $user->hasRole($superAdminRole)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès réservé au super administrateur.'], 403);
            }

            abort(403, 'Accès réservé au super administrateur.');
        }

        return $next($request);
    }
}

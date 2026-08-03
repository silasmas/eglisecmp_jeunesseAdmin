<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint le studio badges aux utilisateurs authentifiés disposant de View:BadgeStudio.
 */
class EnsureBadgeStudioAccess
{
    /**
     * @param Closure(Request): Response $next
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

        if (! $user->can('View:BadgeStudio')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès au studio badges non autorisé.'], 403);
            }

            abort(403, 'Accès au studio badges non autorisé.');
        }

        return $next($request);
    }
}

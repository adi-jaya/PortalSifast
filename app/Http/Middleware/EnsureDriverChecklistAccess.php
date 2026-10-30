<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverChecklistAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->canAccessDriverModule()) {
            abort(403, 'Anda tidak memiliki akses ke modul Driver.');
        }

        return $next($request);
    }
}

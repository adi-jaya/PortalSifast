<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBerkasKepegawaianAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->canAccessBerkasKepegawaian()) {
            abort(403, 'Anda tidak memiliki akses ke modul Berkas Kepegawaian.');
        }

        return $next($request);
    }
}

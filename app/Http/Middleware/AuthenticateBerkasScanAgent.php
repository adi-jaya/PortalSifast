<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBerkasScanAgent
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.berkas_scan.agent_token', '');

        if ($expected === '') {
            return $this->unauthorized('Berkas scan agent token is not configured.');
        }

        $provided = $this->extractToken($request);

        if ($provided === null || ! hash_equals($expected, $provided)) {
            return $this->unauthorized('Invalid berkas scan agent token.');
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->header('X-Berkas-Agent-Token');
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $authorization = $request->header('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            $bearer = trim(substr($authorization, 7));

            return $bearer !== '' ? $bearer : null;
        }

        return null;
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}

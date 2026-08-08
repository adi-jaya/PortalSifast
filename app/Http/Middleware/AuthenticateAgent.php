<?php

namespace App\Http\Middleware;

use App\Models\MonitoredDevice;
use App\Services\Agent\AgentApiKeyService;
use App\Support\AgentLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function __construct(private AgentApiKeyService $apiKeys) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) ($request->headers->get('X-Request-Id') ?: Str::uuid());

        Log::shareContext([
            'request_id' => $requestId,
            'route' => 'agent',
        ]);

        $plainKey = $this->extractBearerToken($request);

        if ($plainKey === null || $plainKey === '') {
            return $this->unauthorized($requestId, 'Missing or invalid Authorization Bearer token.');
        }

        $prefix = $this->apiKeys->prefix($plainKey);

        $candidates = MonitoredDevice::query()
            ->where('api_key_prefix', $prefix)
            ->limit(10)
            ->get();

        $device = $candidates->first(
            fn (MonitoredDevice $candidate): bool => $this->apiKeys->matches($plainKey, $candidate->api_key_hash)
        );

        if ($device === null) {
            AgentLog::warning('Agent authentication failed', [
                'request_id' => $requestId,
                'api_key_prefix' => $prefix,
            ]);

            return $this->unauthorized($requestId, 'Invalid agent API key.');
        }

        Log::shareContext([
            'device_uuid' => $device->uuid,
        ]);

        $request->attributes->set('monitored_device', $device);
        $request->attributes->set('agent_request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }

    private function unauthorized(string $requestId, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'agent_unauthorized',
                'message' => $message,
                'request_id' => $requestId,
            ],
        ], 401);
    }
}

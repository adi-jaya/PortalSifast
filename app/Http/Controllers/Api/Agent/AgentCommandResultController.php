<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\ReportAgentCommandResultRequest;
use App\Models\MonitoredDevice;
use App\Services\Agent\AgentDeviceCommandService;
use Illuminate\Http\JsonResponse;

class AgentCommandResultController extends Controller
{
    public function __invoke(
        ReportAgentCommandResultRequest $request,
        AgentDeviceCommandService $commands,
    ): JsonResponse {
        /** @var MonitoredDevice $device */
        $device = $request->attributes->get('monitored_device');
        $requestId = (string) $request->attributes->get('agent_request_id');
        $validated = $request->validated();

        $command = $commands->recordResult(
            $device,
            (int) $validated['command_id'],
            $validated['status'],
            $validated['result'] ?? [],
        );

        return response()->json([
            'success' => true,
            'data' => [
                'command_id' => $command->id,
                'status' => $command->status,
                'request_id' => $requestId,
            ],
        ]);
    }
}

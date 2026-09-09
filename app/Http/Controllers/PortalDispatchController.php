<?php

namespace App\Http\Controllers;

use App\Models\Portal;
use App\Services\PortalDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortalDispatchController extends Controller
{
    public function dispatch(Request $request, Portal $portal, PortalDispatchService $service): JsonResponse
    {
        Gate::authorize('dispatchToken', $portal);

        $payload = $service->dispatch($request->user(), $portal);

        return response()->json($payload);
    }
}

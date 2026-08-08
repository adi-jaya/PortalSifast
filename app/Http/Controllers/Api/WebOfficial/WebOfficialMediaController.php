<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\UploadWebOfficialMediaRequest;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialMediaController extends Controller
{
    public function upload(
        UploadWebOfficialMediaRequest $request,
        WebOfficialMediaStorageService $storage,
    ): JsonResponse {
        try {
            $result = $storage->store(
                $request->file('file'),
                $request->string('folder', 'informasi')->toString(),
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 201);
    }

    public function destroy(Request $request, WebOfficialMediaStorageService $storage): JsonResponse
    {
        if (! $request->user()?->canManageWebOfficial()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke modul web official.',
            ], 403);
        }

        $validated = $request->validate([
            'path' => ['required', 'string', 'starts_with:webofficial/'],
        ]);

        $deleted = $storage->delete($validated['path']);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan atau tidak dapat dihapus.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $validated['path'],
                'deleted' => true,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialBioLink;
use Illuminate\Http\JsonResponse;

class WebOfficialBioLinkController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json(['success' => true, 'data' => WebOfficialBioLink::publicPage()])
            ->header('Cache-Control', 'public, max-age=300');
    }
}

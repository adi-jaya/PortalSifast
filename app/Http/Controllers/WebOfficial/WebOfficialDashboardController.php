<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('web-official/index');
    }
}

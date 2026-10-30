<?php

namespace App\Http\Controllers;

use App\Services\Portal\PortalAggregatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalAggregatorController extends Controller
{
    public function __construct(
        private PortalAggregatorService $aggregatorService,
    ) {}

    /**
     * Menampilkan antarmuka pengguna agregator portal pelaporan eksternal.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $category = $request->query('category');
        $search = $request->query('search');

        $portals = $this->aggregatorService->getUserPortals(
            $user,
            category: is_string($category) ? $category : null,
            search: is_string($search) ? $search : null,
        );

        $categories = $this->aggregatorService->getCategoriesForUser($user);

        return Inertia::render('portal-pelaporan/index', [
            'portals' => $portals,
            'categories' => $categories,
            'filters' => [
                'category' => is_string($category) && filled($category) ? $category : 'all',
                'search' => is_string($search) ? $search : '',
            ],
        ]);
    }
}

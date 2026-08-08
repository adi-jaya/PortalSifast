<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patroli\StorePatroliTemplateRequest;
use App\Http\Requests\Patroli\UpdatePatroliTemplateRequest;
use App\Models\PatroliTemplate;
use App\Models\PatroliTemplateItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PatroliTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $rows = PatroliTemplate::query()
            ->withCount(['items', 'ruang', 'activeItems'])
            ->when($q !== '', fn ($query) => $query->where('nama', 'like', "%{$q}%"))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('patroli/templates/index', [
            'rows' => $rows,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('patroli/templates/create');
    }

    public function store(StorePatroliTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $template = DB::transaction(function () use ($validated) {
            $template = PatroliTemplate::query()->create([
                'nama' => $validated['nama'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $template->items()->create([
                    'nama' => $item['nama'],
                    'urutan' => $item['urutan'] ?? $index,
                    'is_active' => $item['is_active'] ?? true,
                ]);
            }

            return $template;
        });

        return redirect()
            ->route('patroli.templates.edit', $template)
            ->with('success', 'Template patroli dibuat.');
    }

    public function edit(PatroliTemplate $template): Response
    {
        $template->load('items');

        return Inertia::render('patroli/templates/edit', [
            'template' => [
                'id' => $template->id,
                'nama' => $template->nama,
                'deskripsi' => $template->deskripsi,
                'is_active' => $template->is_active,
                'items' => $template->items->map(fn (PatroliTemplateItem $item) => [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'urutan' => $item->urutan,
                    'is_active' => $item->is_active,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdatePatroliTemplateRequest $request, PatroliTemplate $template): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($template, $validated) {
            $template->update([
                'nama' => $validated['nama'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'is_active' => $validated['is_active'] ?? $template->is_active,
            ]);

            $keepIds = [];
            foreach ($validated['items'] as $index => $item) {
                if (! empty($item['id'])) {
                    $existing = PatroliTemplateItem::query()
                        ->where('patroli_template_id', $template->id)
                        ->whereKey($item['id'])
                        ->first();
                    if ($existing) {
                        $existing->update([
                            'nama' => $item['nama'],
                            'urutan' => $item['urutan'] ?? $index,
                            'is_active' => $item['is_active'] ?? true,
                        ]);
                        $keepIds[] = $existing->id;

                        continue;
                    }
                }

                $created = $template->items()->create([
                    'nama' => $item['nama'],
                    'urutan' => $item['urutan'] ?? $index,
                    'is_active' => $item['is_active'] ?? true,
                ]);
                $keepIds[] = $created->id;
            }

            PatroliTemplateItem::query()
                ->where('patroli_template_id', $template->id)
                ->whereNotIn('id', $keepIds)
                ->update(['is_active' => false]);
        });

        return redirect()
            ->route('patroli.templates.edit', $template)
            ->with('success', 'Template patroli diperbarui.');
    }
}

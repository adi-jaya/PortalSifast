<?php

namespace App\Services\Portal;

use App\Models\Portal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminPortalService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     portals: LengthAwarePaginator,
     *     categories: Collection<int, string>,
     *     filters: array{search: string, category: string, status: string}
     * }
     */
    public function paginatePortals(array $filters = []): array
    {
        $query = Portal::query()->withCount('userCredentials');

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('category', 'like', "%{$escaped}%")
                    ->orWhere('url', 'like', "%{$escaped}%");
            });
        }

        $category = (string) ($filters['category'] ?? '');
        if ($category !== '' && $category !== '_all') {
            $query->where('category', $category);
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $portals = $query->ordered()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Portal $portal): array => [
                'id' => $portal->id,
                'name' => $portal->name,
                'slug' => $portal->slug,
                'category' => $portal->category,
                'url' => $portal->url,
                'url_pattern' => $portal->url_pattern,
                'icon_path' => $portal->icon_path,
                'description' => $portal->description,
                'auth_type' => $portal->auth_type,
                'shared_username' => $portal->shared_username,
                'has_shared_password' => filled($portal->shared_password),
                'is_active' => $portal->is_active,
                'sort_order' => $portal->sort_order,
                'user_credentials_count' => $portal->user_credentials_count ?? 0,
                'updated_at' => $portal->updated_at?->format('Y-m-d H:i'),
            ]);

        $categories = $this->getCategories();

        return [
            'portals' => $portals,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
            ],
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function getCategories(): Collection
    {
        return Portal::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn ($category): string => trim((string) $category))
            ->filter(fn (string $category): bool => $category !== '')
            ->unique(fn (string $category): string => mb_strtolower($category))
            ->sort(fn (string $a, string $b): int => strnatcasecmp($a, $b))
            ->values();
    }

    /**
     * @return array{default_form_config: array<string, mixed>, categories: Collection<int, string>}
     */
    public function getFormData(): array
    {
        $defaultFormConfig = [
            'is_spa' => false,
            'wait_timeout_ms' => 10000,
            'username_field' => [
                'selectors' => ["input[name='username']", "input[name='email']", '#username', '#email'],
            ],
            'password_field' => [
                'selectors' => ["input[name='password']", '#password'],
            ],
            'extra_fields' => [],
            'auto_submit' => false,
        ];

        return [
            'default_form_config' => $defaultFormConfig,
            'categories' => $this->getCategories(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storePortal(array $data): Portal
    {
        if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        if (! isset($data['sort_order']) || $data['sort_order'] === null) {
            $data['sort_order'] = (Portal::max('sort_order') ?? 0) + 10;
        }

        if (empty($data['form_config'])) {
            $data['form_config'] = [
                'is_spa' => false,
                'wait_timeout_ms' => 10000,
                'username_field' => ['selectors' => ["input[name='username']", '#username']],
                'password_field' => ['selectors' => ["input[name='password']", '#password']],
                'extra_fields' => [],
                'auto_submit' => false,
            ];
        }

        return Portal::create($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPortalForEdit(Portal $portal): array
    {
        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'slug' => $portal->slug,
            'category' => $portal->category,
            'url' => $portal->url,
            'url_pattern' => $portal->url_pattern,
            'icon_path' => $portal->icon_path,
            'description' => $portal->description,
            'auth_type' => $portal->auth_type,
            'shared_username' => $portal->shared_username,
            'has_shared_password' => filled($portal->shared_password),
            'shared_extra_fields' => $portal->shared_extra_fields,
            'form_config' => $portal->form_config,
            'is_active' => (bool) $portal->is_active,
            'sort_order' => (int) $portal->sort_order,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePortal(Portal $portal, array $data): Portal
    {
        if (! filled($data['shared_password'] ?? null)) {
            unset($data['shared_password']);
        }

        $portal->update($data);

        return $portal;
    }

    public function toggleActive(Portal $portal): bool
    {
        $newStatus = ! $portal->is_active;
        $portal->update(['is_active' => $newStatus]);

        return $newStatus;
    }

    public function destroyPortal(Portal $portal): bool
    {
        return (bool) $portal->delete();
    }
}

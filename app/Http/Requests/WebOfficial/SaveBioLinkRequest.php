<?php

namespace App\Http\Requests\WebOfficial;

use App\Models\WebOfficialBioLink;
use App\Services\WebOfficialMediaStorageService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Dipakai bersama oleh API admin (JSON) dan panel PortalSifast (Inertia).
 * POST = buat baru (field wajib), PATCH = ubah sebagian.
 */
class SaveBioLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebOfficial() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:60'],
            'description' => ['sometimes', 'nullable', 'string', 'max:100'],
            'url' => [$required, 'string', 'max:500', $this->bioUrlRule()],
            'icon' => [$required, 'string', Rule::in(WebOfficialBioLink::ICONS)],
            'imageUrl' => ['sometimes', 'nullable', 'string', 'max:500', 'url:https'],
            'imageFile' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:'.WebOfficialMediaStorageService::maxSizeKb()],
            'featured' => ['sometimes', 'nullable', 'boolean'],
            'sortOrder' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'nullable', 'boolean'],
            'startsAt' => ['sometimes', 'nullable', 'date'],
            'endsAt' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Aturan yang bergantung pada nilai lama (untuk PATCH) dan jumlah tautan aktif.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $link = $this->bioLink();

            $featured = $this->has('featured') ? $this->boolean('featured') : (bool) $link?->featured;
            $hasImage = $this->hasFile('imageFile')
                || ($this->has('imageUrl') ? filled($this->input('imageUrl')) : filled($link?->image_url));

            if ($featured && ! $hasImage) {
                $validator->errors()->add('featured', 'Tautan unggulan wajib memiliki gambar.');
            }

            $startsAt = $this->has('startsAt') ? $this->input('startsAt') : $link?->starts_at;
            $endsAt = $this->has('endsAt') ? $this->input('endsAt') : $link?->ends_at;

            if (filled($startsAt) && filled($endsAt) && Carbon::parse($endsAt)->lte(Carbon::parse($startsAt))) {
                $validator->errors()->add('endsAt', 'Selesai tayang harus setelah mulai tayang.');
            }

            $willBeActive = $this->has('isActive') ? $this->boolean('isActive') : ($link?->is_active ?? true);
            $becomesActive = $willBeActive && ! ($link?->is_active ?? false);

            if ($becomesActive && WebOfficialBioLink::query()->where('is_active', true)->count() >= WebOfficialBioLink::MAX_ACTIVE) {
                $validator->errors()->add('isActive', 'Maksimal '.WebOfficialBioLink::MAX_ACTIVE.' tautan aktif.');
            }
        }];
    }

    /**
     * Atribut kolom siap disimpan, termasuk URL gambar hasil upload (panel).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(WebOfficialMediaStorageService $storage): array
    {
        $attributes = WebOfficialBioLink::attributesFrom($this->validated());

        if ($this->hasFile('imageFile')) {
            $attributes['image_url'] = $storage->store($this->file('imageFile'), 'bio-links')['url'];
        }

        foreach (['featured', 'is_active'] as $flag) {
            if (array_key_exists($flag, $attributes)) {
                $attributes[$flag] = (bool) $attributes[$flag];
            }
        }

        foreach (['starts_at', 'ends_at'] as $date) {
            if (filled($attributes[$date] ?? null)) {
                $attributes[$date] = Carbon::parse($attributes[$date])->setTimezone(config('app.timezone'));
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul maksimal 60 karakter.',
            'description.max' => 'Deskripsi maksimal 100 karakter.',
            'url.required' => 'URL wajib diisi.',
            'icon.required' => 'Ikon wajib dipilih.',
            'icon.in' => 'Ikon tidak dikenal.',
            'imageUrl.url' => 'URL gambar harus diawali https://.',
            'imageFile.mimes' => 'Format gambar harus JPEG, PNG, atau WebP.',
            'imageFile.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    private function bioLink(): ?WebOfficialBioLink
    {
        $link = $this->route('bioLink');

        return $link instanceof WebOfficialBioLink ? $link : null;
    }

    /**
     * Hanya path internal (/...), http(s)://, tel:, atau mailto:.
     */
    private function bioUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $value = (string) $value;

            $valid = match (true) {
                (bool) preg_match('/[\s\\\\]/', $value) => false,
                str_starts_with($value, '/') => ! str_starts_with($value, '//'),
                (bool) preg_match('#^https?://#i', $value) => filter_var($value, FILTER_VALIDATE_URL) !== false,
                (bool) preg_match('/^tel:\+?[0-9().-]{3,}$/i', $value) => true,
                default => (bool) preg_match('/^mailto:[^@]+@[^@]+\.[^@]+$/i', $value),
            };

            if (! $valid) {
                $fail('URL harus diawali /, https://, http://, tel:, atau mailto:.');
            }
        };
    }
}

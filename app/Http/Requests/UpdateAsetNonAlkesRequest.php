<?php

namespace App\Http\Requests;

use App\Models\AsetNonAlkes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsetNonAlkesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_alat' => trim((string) $this->input('nama_alat', '')),
            'kode' => $this->nullableString('kode'),
            'sinonim' => $this->nullableString('sinonim'),
            'parent_id' => $this->nullableInt('parent_id'),
            'aset_kategori_id' => $this->nullableInt('aset_kategori_id'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $item = $this->route('nonAlkes');

        return [
            'nama_alat' => ['required', 'string', 'min:2', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'sinonim' => ['nullable', 'string', 'max:2000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('aset_non_alkes', 'id')->where(fn ($query) => $query->where('deleted', false)),
                Rule::notIn([$item instanceof AsetNonAlkes ? $item->id : 0]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $item = $this->route('nonAlkes');
                    if (! $item instanceof AsetNonAlkes || $value === null) {
                        return;
                    }

                    $parent = AsetNonAlkes::query()->find($value);
                    if ($parent instanceof AsetNonAlkes && $parent->hasAncestor($item)) {
                        $fail('Induk tidak boleh dirinya sendiri atau turunannya.');
                    }
                },
            ],
            'aset_kategori_id' => ['nullable', 'integer', 'exists:aset_kategori,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_alat.required' => 'Nama katalog wajib diisi.',
            'nama_alat.min' => 'Nama katalog minimal 2 karakter.',
            'parent_id.exists' => 'Induk tidak ditemukan.',
            'parent_id.not_in' => 'Induk tidak boleh dirinya sendiri atau turunannya.',
            'aset_kategori_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->input($key);

        if ($value === null || $value === '' || $value === '__none__') {
            return null;
        }

        return (int) $value;
    }
}

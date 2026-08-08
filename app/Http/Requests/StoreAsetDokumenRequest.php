<?php

namespace App\Http\Requests;

use App\Models\AsetDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAsetDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'tipe' => ['required', 'string', Rule::in(AsetDokumen::TIPE)],
            'lingkup' => ['required', 'string', Rule::in([AsetDokumen::LINGKUP_UNIT, AsetDokumen::LINGKUP_BARANG])],
            'judul' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File dokumen wajib diunggah.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'file.mimes' => 'Format file tidak didukung.',
            'tipe.required' => 'Tipe dokumen wajib dipilih.',
            'lingkup.required' => 'Lingkup lampiran wajib dipilih.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('lingkup') !== AsetDokumen::LINGKUP_BARANG) {
                return;
            }

            /** @var \App\Models\Aset|null $aset */
            $aset = $this->route('aset');
            if ($aset !== null && $aset->aset_barang_id === null) {
                $validator->errors()->add(
                    'lingkup',
                    'Aset ini belum terhubung ke katalog barang; tidak bisa melampirkan ke barang.',
                );
            }
        });
    }
}

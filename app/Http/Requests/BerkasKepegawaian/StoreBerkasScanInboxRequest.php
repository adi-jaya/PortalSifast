<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class StoreBerkasScanInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg', 'max:10240'],
            'suggested_kode' => ['nullable', 'string', 'max:20'],
            'suggested_label' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'ocr_excerpt' => ['nullable', 'string', 'max:5000'],
            'ocr_failed' => ['nullable'],
            'agent_label' => ['nullable', 'string', 'max:255'],
            'original_filename' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dokumen.required' => 'Dokumen wajib diunggah.',
            'dokumen.file' => 'Dokumen harus berupa file.',
            'dokumen.mimes' => 'Dokumen harus berformat PDF, JPG, atau JPEG.',
            'dokumen.max' => 'Dokumen maksimal 10 MB.',
        ];
    }
}

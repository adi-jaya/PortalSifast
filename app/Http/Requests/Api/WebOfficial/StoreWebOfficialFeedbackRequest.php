<?php

namespace App\Http\Requests\Api\WebOfficial;

use App\Models\WebOfficialFeedback;
use App\Support\WebOfficialFeedbackServiceUnits;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreWebOfficialFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'min:2', 'max:200'],
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[\d+\-\s()]+$/'],
            'serviceUnit' => ['required', 'string', Rule::in(WebOfficialFeedbackServiceUnits::all())],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fullName.required' => 'Nama lengkap wajib diisi.',
            'fullName.min' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'phone.min' => 'Nomor HP tidak valid.',
            'phone.max' => 'Nomor HP tidak valid.',
            'phone.regex' => 'Nomor HP tidak valid.',
            'serviceUnit.required' => 'Unit pelayanan wajib dipilih.',
            'serviceUnit.in' => 'Unit pelayanan tidak valid.',
            'rating.required' => 'Rating harus antara 1 dan 5.',
            'rating.min' => 'Rating harus antara 1 dan 5.',
            'rating.max' => 'Rating harus antara 1 dan 5.',
            'message.required' => 'Kritik & saran minimal 10 karakter.',
            'message.min' => 'Kritik & saran minimal 10 karakter.',
            'message.max' => 'Kritik & saran maksimal 1000 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $phone = $this->string('phone')->toString();
            $message = strip_tags($this->string('message')->toString());

            $isDuplicate = WebOfficialFeedback::query()
                ->where('phone', $phone)
                ->where('message', $message)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($isDuplicate) {
                $validator->errors()->add('message', 'Pengaduan serupa baru saja dikirim. Mohon tunggu beberapa menit.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function submissionAttributes(): array
    {
        return [
            'full_name' => $this->string('fullName')->toString(),
            'phone' => $this->string('phone')->toString(),
            'service_unit' => $this->string('serviceUnit')->toString(),
            'rating' => (int) $this->input('rating'),
            'message' => strip_tags($this->string('message')->toString()),
            'status' => 'new',
            'source' => 'website-official',
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ];
    }
}

<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreWebOfficialRujukanFaskesRequest extends FormRequest
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
            'nama_fktp' => ['required', 'string', 'max:100'],
            'nama_perujuk' => ['required', 'string', 'max:60'],
            'no_telp_perujuk' => ['required', 'string', 'regex:/^[\d+\-\s()]+$/', 'min:9', 'max:20'],
            'nama_pasien' => ['required', 'string', 'max:100'],
            'no_ktp' => ['required', 'digits:16'],
            'jk' => ['required', Rule::in(['L', 'P'])],
            'tgl_lahir' => ['nullable', 'date'],
            'umur' => ['nullable', 'string', 'max:15'],
            'no_telp_pasien' => ['nullable', 'string', 'max:20'],
            'diagnosa' => ['required', 'string', 'max:2000'],
            'keluhan_utama' => ['nullable', 'string', 'max:2000'],
            'tanda_vital_kesadaran' => ['nullable', 'string', 'max:30'],
            'tanda_vital_td' => ['nullable', 'string', 'max:15'],
            'tanda_vital_nadi' => ['nullable', 'string', 'max:10'],
            'tanda_vital_rr' => ['nullable', 'string', 'max:10'],
            'tanda_vital_suhu' => ['nullable', 'string', 'max:10'],
            'tanda_vital_spo2' => ['nullable', 'string', 'max:10'],
            'tindakan_diberikan' => ['nullable', 'string', 'max:2000'],
            'transportasi' => ['nullable', 'string', 'max:50'],
            'kebutuhan_khusus' => ['nullable', 'string', 'max:100'],
            'estimasi_tiba' => ['nullable', 'string', 'max:50'],
            '_hp_code' => ['prohibited', 'max:0'],
            '_form_time' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_fktp.required' => 'Nama FKTP wajib diisi.',
            'nama_fktp.max' => 'Nama FKTP maksimal 100 karakter.',
            'nama_perujuk.required' => 'Nama dokter/bidan/perujuk wajib diisi.',
            'nama_perujuk.max' => 'Nama perujuk maksimal 60 karakter.',
            'no_telp_perujuk.required' => 'Nomor kontak perujuk wajib diisi.',
            'no_telp_perujuk.regex' => 'Format nomor telepon perujuk tidak valid.',
            'no_telp_perujuk.min' => 'Nomor kontak perujuk minimal 9 karakter.',
            'no_telp_perujuk.max' => 'Nomor kontak perujuk maksimal 20 karakter.',
            'nama_pasien.required' => 'Nama pasien wajib diisi.',
            'nama_pasien.max' => 'Nama pasien maksimal 100 karakter.',
            'no_ktp.required' => 'Nomor KTP (NIK) pasien wajib diisi.',
            'no_ktp.digits' => 'Nomor KTP (NIK) harus terdiri dari 16 digit angka.',
            'jk.required' => 'Jenis kelamin pasien wajib dipilih.',
            'jk.in' => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
            'tgl_lahir.date' => 'Format tanggal lahir tidak valid.',
            'diagnosa.required' => 'Diagnosa awal/kerja wajib diisi.',
            'diagnosa.max' => 'Diagnosa maksimal 2000 karakter.',
            '_hp_code.prohibited' => 'Pengiriman form tidak valid (honeypot triggered).',
            '_hp_code.max' => 'Pengiriman form tidak valid (honeypot triggered).',
            '_form_time.required' => 'Parameter keamanan form tidak lengkap.',
            '_form_time.integer' => 'Parameter keamanan form tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->has('_form_time')) {
                $formTime = (int) $this->input('_form_time');
                if ((time() - $formTime) < 3) {
                    $validator->errors()->add('_form_time', 'Pengiriman formulir terlalu cepat. Silakan tunggu beberapa detik.');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}

<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportTianjiLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date_format' => 'Format tanggal mulai harus Y-m-d.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.date_format' => 'Format tanggal selesai harus Y-m-d.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = Carbon::createFromFormat('Y-m-d', (string) $this->input('start_date'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', (string) $this->input('end_date'))->startOfDay();

            if ($start->diffInDays($end) > 30) {
                $validator->errors()->add(
                    'end_date',
                    'Rentang tanggal maksimal 31 hari (inklusif).',
                );
            }
        });
    }

    public function startDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', (string) $this->validated('start_date'), config('app.timezone'))
            ->startOfDay();
    }

    public function endDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', (string) $this->validated('end_date'), config('app.timezone'))
            ->endOfDay();
    }
}

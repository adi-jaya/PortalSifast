<?php

namespace App\Http\Requests\Api\Agent;

use App\Models\AgentDeviceCommand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportAgentCommandResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'command_id' => ['required', 'integer'],
            'status' => ['required', 'string', Rule::in([
                AgentDeviceCommand::STATUS_SUCCEEDED,
                AgentDeviceCommand::STATUS_FAILED,
            ])],
            'result' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'command_id.required' => 'ID perintah wajib diisi.',
            'status.required' => 'Status hasil wajib diisi.',
            'status.in' => 'Status hasil tidak valid.',
        ];
    }
}

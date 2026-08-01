<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GatewayMunicipalConfigurationRequest extends FormRequest
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
            'municipal_service_code' => ['required', 'string', 'max:100'],
            'service_description' => ['required', 'string', 'max:5000'],
            'municipal_service_name' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'incentivized_tax' => ['nullable', 'boolean'],
        ];
    }
}

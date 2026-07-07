<?php

namespace App\Http\Requests;

use App\Enums\GenderType;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractWizardRequest extends FormRequest
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
        $clientId = $this->integer('client_id') ?: null;
        $client = $clientId !== null ? Client::query()->find($clientId) : null;

        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:11'],
            'document' => [
                'required',
                'string',
                'size:11',
                Rule::unique('clients', 'document')->ignore($client?->id),
            ],
            'gender' => ['required', 'string', Rule::enum(GenderType::class)],
            'birth_date' => ['required', 'date'],
            'legal_representative' => ['boolean'],
            'legal_representative_name' => [Rule::requiredIf($this->boolean('legal_representative')), 'nullable', 'string', 'max:255'],
            'legal_representative_document' => [Rule::requiredIf($this->boolean('legal_representative')), 'nullable', 'string', 'size:11'],
            'legal_representative_birth_date' => [Rule::requiredIf($this->boolean('legal_representative')), 'nullable', 'date'],
            'address_postal_code' => ['nullable', 'string', 'max:8'],
            'address' => ['nullable', 'string', 'max:200'],
            'address_number' => ['nullable', 'string', 'max:10'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(
                    fn (Builder $query): Builder => $query->where('visibility', 'visible')
                ),
            ],
            'installments' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('plan_tiers', 'quantity')->where(
                    fn (Builder $query): Builder => $query->where('plan_id', $this->integer('plan_id'))
                ),
            ],
            'annotations' => ['nullable', 'string', 'max:500'],
            'accepted_terms' => ['accepted'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
        $rules = [
            'settings' => ['required', 'array'],
        ];

        Setting::query()
            ->select(['name', 'object_type'])
            ->orderBy('id')
            ->get()
            ->each(function (Setting $setting) use (&$rules): void {
                $rules['settings.'.$setting->name] = $this->rulesForType($setting->object_type);
            });

        return $rules;
    }

    /**
     * @return array<int, ValidationRule|string>
     */
    private function rulesForType(string $objectType): array
    {
        return match ($objectType) {
            'boolean', 'bool' => ['boolean'],
            'integer', 'int' => ['nullable', 'integer'],
            'number', 'numeric', 'float', 'decimal' => ['nullable', 'numeric'],
            default => ['nullable', 'string'],
        };
    }
}

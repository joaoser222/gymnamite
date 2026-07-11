<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
        /** @var User|null $user */
        $user = $this->route('user');
        $updating = $user !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
            'password' => $updating
                ? [Rule::excludeIf(blank($this->input('password'))), 'string', Password::default(), 'confirmed']
                : ['required', 'string', Password::default(), 'confirmed'],
            'password_confirmation' => [
                Rule::excludeIf($updating && blank($this->input('password'))),
                'nullable',
                'string',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_id' => blank($this->input('role_id')) ? null : $this->input('role_id'),
        ]);
    }
}

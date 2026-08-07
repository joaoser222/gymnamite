<?php

namespace App\DTOs\Users;

use App\DTOs\Contracts\BaseDTO;

class SaveUserWithPermissionsDTO extends BaseDTO
{
    /**
     * @param  array<int, int|string>  $permission_ids
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?int $id = null,
        public ?int $role_id = null,
        public ?string $password = null,
        public array $permission_ids = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
        ];

        if ($this->password !== null) {
            $attributes['password'] = $this->password;
        }

        return $attributes;
    }

    /**
     * Excludes the password from the action input log.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'permission_ids' => $this->permission_ids,
            'has_password' => $this->password !== null,
        ];
    }
}

<?php

namespace App\DTOs\Roles;

use App\DTOs\Contracts\BaseDTO;

class UpdateRolePermissionsDTO extends BaseDTO
{
    /**
     * @param  array<int, int>  $permission_ids
     */
    public function __construct(
        public int $role_id,
        public array $permission_ids,
    ) {}
}

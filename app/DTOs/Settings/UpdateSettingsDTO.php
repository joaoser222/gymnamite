<?php

namespace App\DTOs\Settings;

use App\DTOs\Contracts\BaseDTO;

class UpdateSettingsDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public array $settings,
    ) {}
}

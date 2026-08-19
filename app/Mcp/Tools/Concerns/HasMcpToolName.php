<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use Illuminate\Support\Str;

trait HasMcpToolName
{
    public function name(): string
    {
        $className = class_basename(static::class);

        return Str::kebab(str_replace('Tool', '', $className));
    }
}

<?php

namespace App\Actions\Contracts;

interface ActionInterface
{
    public function execute(mixed $input): mixed;

    public function authorize(mixed $input): bool;
}

<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TrainerRepositoryInterface extends RepositoryInterface
{
    public function findByDocument(string $document): ?Model;
}

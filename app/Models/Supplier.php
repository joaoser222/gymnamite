<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasVisibility;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'email',
        'document',
        'phone',
        'address',
        'address_number',
        'address_complement',
        'address_state',
        'address_city',
        'address_district',
        'address_postal_code',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasVisibility;

class Supplier extends Model
{
    use HasVisibility;
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'email',
        'document',
        'birth_date',
        'phone',
        'address',
        'address_number',
        'address_complement',
        'address_state',
        'address_city',
        'address_district',
        'address_postal_code'
    ];
}

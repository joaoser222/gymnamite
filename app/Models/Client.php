<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'clients';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'birth_date',
        'gender',
        'profile_image',
        'address',
        'address_number',
        'address_complement',
        'address_state',
        'address_city',
        'address_district',
        'address_postal_code',
        'legal_representative',
        'legal_representative_name',
        'legal_representative_document',
        'legal_representative_birth_date',
        'trainer_id',
        'status',
    ];

    protected $casts = [
        'legal_representative' => 'boolean',
        'birth_date' => 'date',
        'legal_representative_birth_date' => 'date',
        'status' => ClientStatus::class,
    ];

    protected $attributes = [
        'status' => ClientStatus::ACTIVE,
    ];
}

<?php

namespace App\Models;

use App\Enums\FinancialAccountType;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    use HasVisibility;

    protected $table = 'financial_accounts';

    protected $fillable = [
        'name',
        'account_type',
        'balance',
        'holder_name',
        'holder_document',
        'holder_birth_date',
        'bank_account_number',
        'bank_agency',
        'bank_account_type',
        'bank_code',
    ];

    protected $casts = [
        'account_type' => FinancialAccountType::class,
    ];
}

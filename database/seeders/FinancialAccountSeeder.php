<?php

namespace Database\Seeders;

use App\Enums\FinancialAccountType;
use App\Enums\Visibility;
use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FinancialAccount::upsert([[
            'id' => 1,
            'name' => 'Conta Caixa Padrão',
            'account_type' => FinancialAccountType::CASH->value,
            'balance' => 0,
            'visibility' => Visibility::VISIBLE->value,
        ]], ['id'], ['name', 'account_type', 'balance']);
    }
}

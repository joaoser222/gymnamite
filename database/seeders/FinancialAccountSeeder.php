<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialAccount;
use App\Enums\FinancialAccountType;
use App\Enums\Visibility;


class FinancialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FinancialAccount::upsert([[
            'id'=>1,
            'name'=>'Conta Caixa Padrão',
            'account_type'=>FinancialAccountType::CASH->value,
            'balance'=>0,
            'visibility'=>Visibility::VISIBLE->value
        ]],['id'],['name','account_type','balance']);
    }
}

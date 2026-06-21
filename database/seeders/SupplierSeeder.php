<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Enums\Visibility;

class SupplierSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::upsert([[
            'id' => 1,
            'name' => 'FORNECEDOR PADRÃO',
            'document' => '00000000000000',
            'phone' => '99999999999',
            'visibility' => Visibility::VISIBLE->value
        ]],['id'],['name','document','phone']);
    }
}

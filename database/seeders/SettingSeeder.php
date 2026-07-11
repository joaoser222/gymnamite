<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::upsert([
            ['name' => 'contract_default_category', 'label' => 'Categoria de Contratos', 'content' => '0', 'object_type' => 'int'],
            ['name' => 'purchase_default_category', 'label' => 'Categoria de Compras', 'content' => '0', 'object_type' => 'int'],
            ['name' => 'sale_default_category', 'label' => 'Categoria de Vendas', 'content' => '0', 'object_type' => 'int'],
            ['name' => 'direct_lesson_default_category', 'label' => 'Categoria de Aula Avulsa', 'content' => '0', 'object_type' => 'int'],
            ['name' => 'default_financial_account', 'label' => 'Conta Padrão', 'content' => '0', 'object_type' => 'int'],
        ], ['name'], ['label', 'content', 'object_type']);
    }
}

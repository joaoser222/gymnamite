<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CostCenter;
use App\Enums\OperationType;

class CostCenterSeeder extends Seeder
{
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CostCenter::upsert([
            ['id' => 1,'name' => 'Receitas','color' => '#1dd1a1','operation_type' => OperationType::RECEIVABLE->value],
            ['id' => 2,'name' => 'Deduções e Abatimentos','color' => '#feca57','operation_type' => OperationType::PAYABLE->value],
            ['id' => 3,'name' => 'Custo de Produtos','color' => '#5f27cd','operation_type' => OperationType::PAYABLE->value],
            ['id' => 4,'name' => 'Despesas Administrativas','color' => '#B53471','operation_type' => OperationType::PAYABLE->value],
            ['id' => 5,'name' => 'Despesas com Vendas','color' => '#ee5253','operation_type' => OperationType::PAYABLE->value],
            ['id' => 6,'name' => 'Despesas Financeiras','color' => '#006266','operation_type' => OperationType::PAYABLE->value],
        ],['id'],['name','color','operation_type']);
    }
}

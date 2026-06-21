<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;
use App\Enums\OperationType;

class FinancialCategorySeeder extends Seeder
{
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FinancialCategory::upsert([
            ['name' => 'Venda de Produtos','operation' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1'],
            ['name' => 'Prestação de Serviços','operation' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1'],
            ['name' => 'Outras Receitas','operation' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1'],
            ['name' => 'Cancelamentos','operation' => OperationType::PAYABLE->value,'cost_center_id' => 2,'color' => '#feca57'],
            ['name' => 'Impostos','operation' => OperationType::PAYABLE->value,'cost_center_id' => 2, 'color' => '#feca57'],
            ['name' => 'Compra de Equipamentos','operation' => OperationType::PAYABLE->value,'cost_center_id' => 3, 'color' => '#5f27cd'],
            ['name' => 'Frete','operation' => OperationType::PAYABLE->value,'cost_center_id' => 3, 'color' => '#5f27cd'],
            ['name' => 'Aluguel','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471'],
            ['name' => 'Energia','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471'],
            ['name' => 'Material de Escritório','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471'],
            ['name' => 'Telefone','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Internet','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471'],
            ['name' => 'Material de Limpeza','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Material de Informática','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Serviços Contábeis','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Pagamento de Funcionários','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Vale Transporte','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Vale Alimentação','operation' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471'],
            ['name' => 'Comissões','operation' => OperationType::PAYABLE->value,'cost_center_id' => 5, 'color' => '#ee5253'],
            ['name' => 'Empréstimos','operation' => OperationType::PAYABLE->value,'cost_center_id' => 6, 'color' => '#006266'],
            ['name' => 'Despesas Bancárias','operation' => OperationType::PAYABLE->value,'cost_center_id' => 6,'color' => '#006266'],
            ['name' => 'Juros E Multas','operation' => OperationType::PAYABLE->value,'cost_center_id' => 6,'color' => '#006266'],
            ['name' => 'Outras Despesas','operation' => OperationType::PAYABLE->value,'cost_center_id' => 6, 'color' => '#006266']
        ],['name','cost_center_id'],['color','operation']);
    }
}

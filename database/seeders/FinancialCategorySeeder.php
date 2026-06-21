<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;
use App\Enums\OperationType;
use App\Enums\Visibility;
class FinancialCategorySeeder extends Seeder
{
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FinancialCategory::upsert([
            ['name' => 'Venda de Produtos','operation_type' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Prestação de Serviços','operation_type' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Outras Receitas','operation_type' => OperationType::RECEIVABLE->value,'cost_center_id' => 1,'color' => '#1dd1a1','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Cancelamentos','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 2,'color' => '#feca57','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Impostos','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 2, 'color' => '#feca57','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Compra de Equipamentos','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 3, 'color' => '#5f27cd','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Frete','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 3, 'color' => '#5f27cd','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Aluguel','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Energia','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Material de Escritório','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Telefone','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Internet','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4, 'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Material de Limpeza','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Material de Informática','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Serviços Contábeis','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Pagamento de Funcionários','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Vale Transporte','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Vale Alimentação','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 4,'color' => '#B53471','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Comissões','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 5, 'color' => '#ee5253','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Empréstimos','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 6, 'color' => '#006266','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Despesas Bancárias','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 6,'color' => '#006266','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Juros E Multas','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 6,'color' => '#006266','visibility'=>Visibility::VISIBLE->value],
            ['name' => 'Outras Despesas','operation_type' => OperationType::PAYABLE->value,'cost_center_id' => 6, 'color' => '#006266','visibility'=>Visibility::VISIBLE->value]
        ],['name','cost_center_id'],['color','operation_type']);
    }
}

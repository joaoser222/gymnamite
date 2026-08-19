<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Gymnamite')]
#[Version('1.0.0')]
#[Instructions('Gestão de academia: clientes, contratos, planos, modalidades, produtos, vendas, compras, aulas diretas, cupons, fornecedores, treinadores, categorias financeiras, centros de custo, contas financeiras, contas a pagar, contas a receber, movimentações e gateway de pagamento.')]
class GymnamiteServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        // Clients
        \App\Mcp\Tools\CreateClientTool::class,
        \App\Mcp\Tools\UpdateClientTool::class,
        // Contracts
        \App\Mcp\Tools\CreateContractTool::class,
        \App\Mcp\Tools\UpdateContractTool::class,
        \App\Mcp\Tools\CancelContractTool::class,
        \App\Mcp\Tools\FindClientByDocumentTool::class,
        // Sales
        \App\Mcp\Tools\CreateSaleTool::class,
        \App\Mcp\Tools\UpdateSaleTool::class,
        // Purchases
        \App\Mcp\Tools\CreatePurchaseTool::class,
        \App\Mcp\Tools\UpdatePurchaseTool::class,
        // Direct Lessons
        \App\Mcp\Tools\CreateDirectLessonTool::class,
        \App\Mcp\Tools\UpdateDirectLessonTool::class,
        // Plans
        \App\Mcp\Tools\CreatePlanTool::class,
        \App\Mcp\Tools\UpdatePlanTool::class,
        // Modalities
        \App\Mcp\Tools\CreateModalityTool::class,
        \App\Mcp\Tools\UpdateModalityTool::class,
        // Products
        \App\Mcp\Tools\CreateProductTool::class,
        \App\Mcp\Tools\UpdateProductTool::class,
        // Gateway
        \App\Mcp\Tools\CreateGatewayAccountTool::class,
        \App\Mcp\Tools\UpdateGatewayAccountTool::class,
        \App\Mcp\Tools\ConfigureFiscalDataTool::class,
        \App\Mcp\Tools\CreateGatewayTransferTool::class,
        // Receivables
        \App\Mcp\Tools\MarkReceivablePaidTool::class,
        \App\Mcp\Tools\RequestGatewayInvoiceTool::class,
        // Reference Data
        \App\Mcp\Tools\CreateCouponTool::class,
        \App\Mcp\Tools\UpdateCouponTool::class,
        \App\Mcp\Tools\CreateTrainerTool::class,
        \App\Mcp\Tools\UpdateTrainerTool::class,
        \App\Mcp\Tools\CreateSupplierTool::class,
        \App\Mcp\Tools\UpdateSupplierTool::class,
        \App\Mcp\Tools\CreateFinancialCategoryTool::class,
        \App\Mcp\Tools\UpdateFinancialCategoryTool::class,
        \App\Mcp\Tools\CreateCostCenterTool::class,
        \App\Mcp\Tools\UpdateCostCenterTool::class,
        \App\Mcp\Tools\CreatePlanCategoryTool::class,
        \App\Mcp\Tools\UpdatePlanCategoryTool::class,
        \App\Mcp\Tools\CreateFinancialAccountTool::class,
        \App\Mcp\Tools\UpdateFinancialAccountTool::class,
        // Payables
        \App\Mcp\Tools\CreatePayableTool::class,
        \App\Mcp\Tools\UpdatePayableTool::class,
        // Admin
        \App\Mcp\Tools\SaveUserTool::class,
        \App\Mcp\Tools\UpdateRolePermissionsTool::class,
        \App\Mcp\Tools\UpdateSettingsTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}

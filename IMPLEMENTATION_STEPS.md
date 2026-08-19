# MCP Implementation Steps — Gymnamite

## Overview

Expose Gymnamite's business operations as MCP tools and resources using `laravel/mcp`. The existing Action/DTO/Repository architecture maps directly to MCP: **Actions → Tools** (write), **Repositories → Resources** (read).

---

## Step 0: Complete Action/DTO/Repository Coverage

8 modules currently use generic CRUD without Actions. 3 modules have no repository at all. These must be migrated before MCP tools can wrap them.

### 0.1 Modules missing repositories

| Module | Model | Has Interface? | Has Implementation? |
|---|---|---|---|
| FinancialCategory | `FinancialCategory` | ❌ | ❌ |
| CostCenter | `CostCenter` | ❌ | ❌ |
| FinancialAccount | `FinancialAccount` | ❌ | ❌ |

**Create for each:** interface in `app/Repositories/Contracts/`, implementation in `app/Repositories/Eloquent/`, bind in `AppServiceProvider`.

### 0.2 Modules missing DTOs

| Module | Has DTOs? | Fields |
|---|---|---|
| Coupon | ❌ | `code`, `percent`, `discount_limit`, `duration`, `expiration_date` |
| Trainer | ❌ | `name`, `email`, `document`, `birth_date`, `phone`, `gender`, `address*` (10 fields) |
| Supplier | ❌ | `name`, `email`, `document`, `phone`, `address*` (9 fields) |
| FinancialCategory | ❌ | `name`, `color`, `operation_type`, `cost_center_id` |
| CostCenter | ❌ | `name`, `color`, `operation_type` |
| PlanCategory | ❌ | `name` |
| FinancialAccount | ❌ | `name`, `account_type`, `holder_name`, `holder_document`, `holder_birth_date`, `bank_account_number`, `bank_agency`, `bank_account_type`, `bank_code` |
| Payable | ✅ (unused) | `CreatePayableDTO` + `UpdatePayableDTO` already exist — wire into controller |

**Create for each:** `Create{Module}DTO`, `Update{Module}DTO`, `ActionResultDTO` (if not shared). Use `Spatie\LaravelData` with validation attributes following the existing pattern (e.g., `Modality` DTOs).

### 0.3 Modules missing Actions

| Module | Actions to Create | Permission |
|---|---|---|
| Coupon | `CreateCouponAction`, `UpdateCouponAction` | `coupons.create`, `coupons.update` |
| Trainer | `CreateTrainerAction`, `UpdateTrainerAction` | `trainers.create`, `trainers.update` |
| Supplier | `CreateSupplierAction`, `UpdateSupplierAction` | `suppliers.create`, `suppliers.update` |
| FinancialCategory | `CreateFinancialCategoryAction`, `UpdateFinancialCategoryAction` | `financial_categories.create`, `financial_categories.update` |
| CostCenter | `CreateCostCenterAction`, `UpdateCostCenterAction` | `cost_centers.create`, `cost_centers.update` |
| PlanCategory | `CreatePlanCategoryAction`, `UpdatePlanCategoryAction` | `plan_categories.create`, `plan_categories.update` |
| FinancialAccount | `CreateFinancialAccountAction`, `UpdateFinancialAccountAction` | `financial_accounts.create`, `financial_accounts.update` |
| Payable | `CreatePayableAction`, `UpdatePayableAction` | `payables.create`, `payables.update` |

**Pattern per Action:**
1. Extends `BaseAction`
2. Constructor-injects the repository interface
3. `handle(mixed $input)` validates DTO type, calls `$this->repository->create($input->toArray())` or `->update($model, $data)`
4. Returns `ActionResultDTO::success($model)` or `::failure($errors)`

### 0.4 Wire controllers to Actions

After creating Actions, update each controller's `store()` and `update()` to delegate:

```php
// Before (generic CRUD)
// CrudModuleController::store() → $this->newModelQuery()->create($validated)

// After
public function store(Request $request): RedirectResponse|JsonResponse
{
    $this->authorizeAccess(AccessAction::CREATE);

    $dto = CreateCouponDTO::from($this->validatedRequestData($request, $this->storeRequestClass()));
    $result = $this->createCoupon->execute($dto);

    if (! $result->success) {
        return $this->actionFailureResponse($request, $result->errors, $result->message);
    }

    return $request->expectsJson()
        ? response()->json($result->data, 201)
        : redirect()->route($this->routePrefix().'.index');
}
```

### 0.5 Step 0 execution order

| Order | Task | Files |
|---|---|---|
| 0.5.1 | Create 3 missing repositories (interface + impl + binding) | `app/Repositories/`, `AppServiceProvider` |
| 0.5.2 | Create DTOs for 7 modules (Payable already has them) | `app/DTOs/{Module}/` |
| 0.5.3 | Create 16 Actions (2 per module × 8 modules) | `app/Actions/{Module}/` |
| 0.5.4 | Update 8 controllers to delegate to Actions | `app/Http/Controllers/{Module}Controller.php` |
| 0.5.5 | Run `pint --dirty` + `test --compact` | — |

---

## Step 1: Infrastructure

### 1.1 Install package

```bash
composer require laravel/mcp
php artisan vendor:publish --tag=ai-routes
```

### 1.2 Create server

```bash
php artisan make:mcp-server GymnamiteServer
```

Register in `routes/ai.php`:

```php
use App\Mcp\Servers\GymnamiteServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/gymnamite', GymnamiteServer::class)
    ->middleware(['auth', 'throttle:mcp']);
```

### 1.3 Authentication

MCP tools mutate data. The server must require an authenticated user. Use `shouldRegister` on each tool to check permissions via the existing `AccessModule`/`Gate` system.

---

## Step 2: Tools (Write Operations)

Each MCP Tool wraps one Action. The tool's `schema()` method maps the DTO fields. The `handle()` method constructs the DTO and calls `$action->execute()`.

### 2.1 Module mapping

| Tool Class | Action | DTO | Permission |
|---|---|---|---|
| **Clients** | | | |
| `CreateClientTool` | `CreateClientAction` | `CreateClientDTO` | `clients.create` |
| `UpdateClientTool` | `UpdateClientAction` | `UpdateClientDTO` | `clients.update` |
| **Contracts** | | | |
| `CreateContractTool` | `CreateContractAction` | `CreateContractDTO` | `contracts.create` |
| `UpdateContractTool` | `UpdateContractAction` | `UpdateContractDTO` | `contracts.update` |
| `CancelContractTool` | `CancelContractAction` | `CancelContractDTO` | `contracts.update` |
| `FindClientByDocumentTool` | `FindClientAction` | `string` (document) | `contracts.create` |
| **Sales** | | | |
| `CreateSaleTool` | `CreateSaleAction` | `CreateSaleDTO` | `sales.create` |
| `UpdateSaleTool` | `UpdateSaleAction` | `UpdateSaleDTO` | `sales.update` |
| **Purchases** | | | |
| `CreatePurchaseTool` | `CreatePurchaseAction` | `CreatePurchaseDTO` | `purchases.create` |
| `UpdatePurchaseTool` | `UpdatePurchaseAction` | `UpdatePurchaseDTO` | `purchases.update` |
| **Direct Lessons** | | | |
| `CreateDirectLessonTool` | `CreateDirectLessonAction` | `CreateDirectLessonDTO` | `direct_lessons.create` |
| `UpdateDirectLessonTool` | `UpdateDirectLessonAction` | `UpdateDirectLessonDTO` | `direct_lessons.update` |
| **Plans** | | | |
| `CreatePlanTool` | `CreatePlanAction` | `CreatePlanDTO` | `plans.create` |
| `UpdatePlanTool` | `UpdatePlanAction` | `UpdatePlanDTO` | `plans.update` |
| **Modalities** | | | |
| `CreateModalityTool` | `CreateModalityAction` | `CreateModalityDTO` | `modalities.create` |
| `UpdateModalityTool` | `UpdateModalityAction` | `UpdateModalityDTO` | `modalities.update` |
| **Products** | | | |
| `CreateProductTool` | `CreateProductAction` | `CreateProductDTO` | `products.create` |
| `UpdateProductTool` | `UpdateProductAction` | `UpdateProductDTO` | `products.update` |
| **Gateway** | | | |
| `CreateGatewayAccountTool` | `CreateGatewayAccountAction` | `CreateGatewayAccountDTO` | `gateway_accounts.create` |
| `UpdateGatewayAccountTool` | `UpdateGatewayAccountAction` | `UpdateGatewayAccountDTO` | `gateway_accounts.update` |
| `ConfigureFiscalDataTool` | `ConfigureFiscalDataAction` | `ConfigureFiscalDataDTO` | `gateway_accounts.update` |
| `CreateGatewayTransferTool` | `CreateGatewayTransferAction` | `array` | `gateway_transfers.create` |
| **Receivables** | | | |
| `MarkReceivablePaidTool` | `MarkReceivablePaidAction` | `MarkReceivablePaidDTO` | `receivables.update` |
| `RequestGatewayInvoiceTool` | `RequestGatewayInvoiceAction` | `RequestGatewayInvoiceDTO` | `receivables.update` |
| **Reference Data (from Step 0)** | | | |
| `CreateCouponTool` | `CreateCouponAction` | `CreateCouponDTO` | `coupons.create` |
| `UpdateCouponTool` | `UpdateCouponAction` | `UpdateCouponDTO` | `coupons.update` |
| `CreateTrainerTool` | `CreateTrainerAction` | `CreateTrainerDTO` | `trainers.create` |
| `UpdateTrainerTool` | `UpdateTrainerAction` | `UpdateTrainerDTO` | `trainers.update` |
| `CreateSupplierTool` | `CreateSupplierAction` | `CreateSupplierDTO` | `suppliers.create` |
| `UpdateSupplierTool` | `UpdateSupplierAction` | `UpdateSupplierDTO` | `suppliers.update` |
| `CreateFinancialCategoryTool` | `CreateFinancialCategoryAction` | `CreateFinancialCategoryDTO` | `financial_categories.create` |
| `UpdateFinancialCategoryTool` | `UpdateFinancialCategoryAction` | `UpdateFinancialCategoryDTO` | `financial_categories.update` |
| `CreateCostCenterTool` | `CreateCostCenterAction` | `CreateCostCenterDTO` | `cost_centers.create` |
| `UpdateCostCenterTool` | `UpdateCostCenterAction` | `UpdateCostCenterDTO` | `cost_centers.update` |
| `CreatePlanCategoryTool` | `CreatePlanCategoryAction` | `CreatePlanCategoryDTO` | `plan_categories.create` |
| `UpdatePlanCategoryTool` | `UpdatePlanCategoryAction` | `UpdatePlanCategoryDTO` | `plan_categories.update` |
| `CreateFinancialAccountTool` | `CreateFinancialAccountAction` | `CreateFinancialAccountDTO` | `financial_accounts.create` |
| `UpdateFinancialAccountTool` | `UpdateFinancialAccountAction` | `UpdateFinancialAccountDTO` | `financial_accounts.update` |
| **Payables** | | | |
| `CreatePayableTool` | `CreatePayableAction` | `CreatePayableDTO` | `payables.create` |
| `UpdatePayableTool` | `UpdatePayableAction` | `UpdatePayableDTO` | `payables.update` |
| **Admin** | | | |
| `SaveUserTool` | `SaveUserWithPermissionsAction` | `SaveUserWithPermissionsDTO` | `users.update` |
| `UpdateRolePermissionsTool` | `UpdateRolePermissionsAction` | `UpdateRolePermissionsDTO` | `roles.update` |
| `UpdateSettingsTool` | `UpdateSettingsAction` | `UpdateSettingsDTO` | `settings.update` |

**Total: 38 tools**

### 2.2 Tool skeleton

Each tool follows this pattern:

```php
<?php

namespace App\Mcp\Tools;

use App\Actions\Clients\CreateClientAction;
use App\DTOs\Clients\CreateClientDTO;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Cria um novo cliente no sistema')]
class CreateClientTool extends Tool
{
    public function __construct(
        protected CreateClientAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ], [
            'name.required' => 'O nome do cliente é obrigatório.',
            'document.required' => 'O documento (CPF/CNPJ) é obrigatório.',
        ]);

        $dto = CreateClientDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors));
        }

        return Response::structured([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'document' => $result->data->document,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome completo do cliente')->required(),
            'document' => $schema->string()->description('CPF ou CNPJ (somente dígitos)')->required(),
            'email' => $schema->string()->description('E-mail do cliente')->nullable(),
        ];
    }
}
```

### 2.3 Annotations

| Tool type | Annotation |
|---|---|
| Create tools | `#[IsIdempotent(false)]` |
| Update tools | `#[IsIdempotent(true)]` |
| Cancel/delete tools | `#[IsDestructive(true)]` |
| Find/search tools | `#[IsReadOnly]` |

### 2.4 Permission gating

Use `shouldRegister` to hide tools the current user cannot access:

```php
public function shouldRegister(Request $request): bool
{
    return $request?->user()?->can('clients.create') ?? false;
}
```

---

## Step 3: Resources (Read Operations)

Each MCP Resource wraps a Repository query method. Resources expose data as context for AI models.

### 3.1 Detail resources

| Resource Class | Repository Method | URI Template |
|---|---|---|
| `ClientResource` | `findWithRelations(id)` | `gymnamite://clients/{id}` |
| `ContractResource` | `findWithRelations(id)` | `gymnamite://contracts/{id}` |
| `InvoiceResource` | `findWithRelations(id)` | `gymnamite://invoices/{id}` |
| `SaleResource` | `findWithRelations(id)` | `gymnamite://sales/{id}` |
| `PurchaseResource` | `findWithRelations(id)` | `gymnamite://purchases/{id}` |
| `DirectLessonResource` | `findWithRelations(id)` | `gymnamite://direct-lessons/{id}` |
| `PlanResource` | `findWithRelations(id)` | `gymnamite://plans/{id}` |
| `ModalityResource` | `findWithRelations(id)` | `gymnamite://modalities/{id}` |
| `ProductResource` | `findWithRelations(id)` | `gymnamite://products/{id}` |
| `ReceivableResource` | `findWithRelations(id)` | `gymnamite://receivables/{id}` |
| `PayableResource` | `findWithRelations(id)` | `gymnamite://payables/{id}` |
| `MovementResource` | `findWithRelations(id)` | `gymnamite://movements/{id}` |
| `GatewayAccountResource` | `findWithRelations(id)` | `gymnamite://gateway-accounts/{id}` |

### 3.2 List/search resources

| Resource Class | Repository Method | URI Template |
|---|---|---|
| `ClientsListResource` | `paginate(filters)` | `gymnamite://clients` |
| `ContractsListResource` | `paginate(filters)` | `gymnamite://contracts` |
| `InvoicesListResource` | `paginate(filters)` | `gymnamite://invoices` |
| `SalesListResource` | `paginate(filters)` | `gymnamite://sales` |
| `PurchasesListResource` | `paginate(filters)` | `gymnamite://purchases` |
| `ReceivablesListResource` | `findPending()` | `gymnamite://receivables/pending` |
| `PayablesListResource` | `findPending()` | `gymnamite://payables/pending` |
| `OverdueReceivablesResource` | `findOverdue()` | `gymnamite://receivables/overdue` |
| `MovementsByDateResource` | `findByDateRange(start, end)` | `gymnamite://movements/range` |

### 3.3 Resource skeleton

```php
<?php

namespace App\Mcp\Resources;

use App\Repositories\Contracts\ClientRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Detalhes de um cliente por ID')]
#[MimeType('application/json')]
class ClientResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected ClientRepositoryInterface $clients,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('gymnamite://clients/{id}');
    }

    public function handle(Request $request): Response
    {
        $client = $this->clients->findWithRelations((int) $request->get('id'));

        if (! $client) {
            return Response::error('Cliente não encontrado.');
        }

        return Response::structured($client->toArray());
    }
}
```

---

## Step 4: Server Registration

Register all tools and resources in the server:

```php
<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server;

#[Name('Gymnamite')]
#[Version('1.0.0')]
#[Instructions('Gestão de academia: clientes, contratos, planos, vendas, compras, aulas, financeiro e gateway de pagamento.')]
class GymnamiteServer extends Server
{
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
        // Reference Data (from Step 0)
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

    protected array $resources = [
        // Detail resources
        \App\Mcp\Resources\ClientResource::class,
        \App\Mcp\Resources\ContractResource::class,
        \App\Mcp\Resources\InvoiceResource::class,
        \App\Mcp\Resources\SaleResource::class,
        \App\Mcp\Resources\PurchaseResource::class,
        \App\Mcp\Resources\DirectLessonResource::class,
        \App\Mcp\Resources\PlanResource::class,
        \App\Mcp\Resources\ModalityResource::class,
        \App\Mcp\Resources\ProductResource::class,
        \App\Mcp\Resources\ReceivableResource::class,
        \App\Mcp\Resources\PayableResource::class,
        \App\Mcp\Resources\MovementResource::class,
        \App\Mcp\Resources\GatewayAccountResource::class,
        // List resources
        \App\Mcp\Resources\ClientsListResource::class,
        \App\Mcp\Resources\ContractsListResource::class,
        \App\Mcp\Resources\InvoicesListResource::class,
        \App\Mcp\Resources\SalesListResource::class,
        \App\Mcp\Resources\PurchasesListResource::class,
        \App\Mcp\Resources\ReceivablesListResource::class,
        \App\Mcp\Resources\PayablesListResource::class,
        \App\Mcp\Resources\OverdueReceivablesResource::class,
        \App\Mcp\Resources\MovementsByDateResource::class,
    ];

    protected array $prompts = [];
}
```

---

## Step 5: Testing

### 5.1 Action tests (for Step 0 modules)

For each new Action, test happy path and validation failure:

```php
it('creates a coupon via action', function () {
    $dto = CreateCouponDTO::from([
        'code' => 'DESCONTO10',
        'percent' => 10.0,
    ]);

    $result = app(CreateCouponAction::class)->execute($dto);

    expect($result->success)->toBeTrue();
    expect($result->data->code)->toBe('DESCONTO10');
});
```

### 5.2 Tool tests

For each MCP tool, test:
- Happy path: valid DTO → success response
- Validation failure: invalid input → error message
- Authorization failure: missing permission → tool not registered (`shouldRegister` returns false)
- Action failure: business rule violation → error response

```php
it('creates a client via MCP tool', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clients.create');

    $response = $this->actingAs($user)->postJson('/mcp/gymnamite', [
        'jsonrpc' => '2.0',
        'method' => 'tools/call',
        'params' => [
            'name' => 'create-client',
            'arguments' => [
                'name' => 'João Silva',
                'document' => '12345678901',
            ],
        ],
    ]);

    $response->assertOk();
});
```

### 5.3 Resource tests

```php
it('returns client data via MCP resource', function () {
    $client = Client::factory()->create();

    $response = $this->getJson("/mcp/gymnamite?uri=gymnamite://clients/{$client->id}");

    $response->assertOk();
});
```

---

## Step 6: File Structure

```
app/Actions/
├── Coupon/
│   ├── CreateCouponAction.php          (new - Step 0)
│   └── UpdateCouponAction.php          (new - Step 0)
├── Trainer/
│   ├── CreateTrainerAction.php          (new - Step 0)
│   └── UpdateTrainerAction.php          (new - Step 0)
├── Supplier/
│   ├── CreateSupplierAction.php         (new - Step 0)
│   └── UpdateSupplierAction.php         (new - Step 0)
├── FinancialCategories/
│   ├── CreateFinancialCategoryAction.php (new - Step 0)
│   └── UpdateFinancialCategoryAction.php (new - Step 0)
├── CostCenters/
│   ├── CreateCostCenterAction.php       (new - Step 0)
│   └── UpdateCostCenterAction.php       (new - Step 0)
├── PlanCategories/
│   ├── CreatePlanCategoryAction.php     (new - Step 0)
│   └── UpdatePlanCategoryAction.php     (new - Step 0)
├── FinancialAccounts/
│   ├── CreateFinancialAccountAction.php (new - Step 0)
│   └── UpdateFinancialAccountAction.php (new - Step 0)
└── Payables/
    ├── CreatePayableAction.php          (new - Step 0)
    └── UpdatePayableAction.php          (new - Step 0)

app/DTOs/
├── Coupon/
│   ├── CreateCouponDTO.php              (new - Step 0)
│   ├── UpdateCouponDTO.php              (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── Trainer/
│   ├── CreateTrainerDTO.php             (new - Step 0)
│   ├── UpdateTrainerDTO.php             (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── Supplier/
│   ├── CreateSupplierDTO.php            (new - Step 0)
│   ├── UpdateSupplierDTO.php            (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── FinancialCategories/
│   ├── CreateFinancialCategoryDTO.php   (new - Step 0)
│   ├── UpdateFinancialCategoryDTO.php   (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── CostCenters/
│   ├── CreateCostCenterDTO.php          (new - Step 0)
│   ├── UpdateCostCenterDTO.php          (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── PlanCategories/
│   ├── CreatePlanCategoryDTO.php        (new - Step 0)
│   ├── UpdatePlanCategoryDTO.php        (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
├── FinancialAccounts/
│   ├── CreateFinancialAccountDTO.php    (new - Step 0)
│   ├── UpdateFinancialAccountDTO.php    (new - Step 0)
│   └── ActionResultDTO.php              (new - Step 0)
└── Payables/
    ├── CreatePayableDTO.php             (new - Step 0)
    ├── UpdatePayableDTO.php             (new - Step 0)
    └── ActionResultDTO.php              (new - Step 0)

app/Repositories/
├── Contracts/
│   ├── FinancialCategoryRepositoryInterface.php  (new - Step 0)
│   ├── CostCenterRepositoryInterface.php         (new - Step 0)
│   └── FinancialAccountRepositoryInterface.php   (new - Step 0)
└── Eloquent/
    ├── EloquentFinancialCategoryRepository.php   (new - Step 0)
    ├── EloquentCostCenterRepository.php          (new - Step 0)
    └── EloquentFinancialAccountRepository.php    (new - Step 0)

app/Mcp/
├── Servers/
│   └── GymnamiteServer.php
├── Tools/
│   ├── CreateClientTool.php
│   ├── UpdateClientTool.php
│   ├── CreateContractTool.php
│   ├── UpdateContractTool.php
│   ├── CancelContractTool.php
│   ├── FindClientByDocumentTool.php
│   ├── CreateSaleTool.php
│   ├── UpdateSaleTool.php
│   ├── CreatePurchaseTool.php
│   ├── UpdatePurchaseTool.php
│   ├── CreateDirectLessonTool.php
│   ├── UpdateDirectLessonTool.php
│   ├── CreatePlanTool.php
│   ├── UpdatePlanTool.php
│   ├── CreateModalityTool.php
│   ├── UpdateModalityTool.php
│   ├── CreateProductTool.php
│   ├── UpdateProductTool.php
│   ├── CreateGatewayAccountTool.php
│   ├── UpdateGatewayAccountTool.php
│   ├── ConfigureFiscalDataTool.php
│   ├── CreateGatewayTransferTool.php
│   ├── MarkReceivablePaidTool.php
│   ├── RequestGatewayInvoiceTool.php
│   ├── CreateCouponTool.php             (new - Step 0)
│   ├── UpdateCouponTool.php             (new - Step 0)
│   ├── CreateTrainerTool.php            (new - Step 0)
│   ├── UpdateTrainerTool.php            (new - Step 0)
│   ├── CreateSupplierTool.php           (new - Step 0)
│   ├── UpdateSupplierTool.php           (new - Step 0)
│   ├── CreateFinancialCategoryTool.php  (new - Step 0)
│   ├── UpdateFinancialCategoryTool.php  (new - Step 0)
│   ├── CreateCostCenterTool.php         (new - Step 0)
│   ├── UpdateCostCenterTool.php         (new - Step 0)
│   ├── CreatePlanCategoryTool.php       (new - Step 0)
│   ├── UpdatePlanCategoryTool.php       (new - Step 0)
│   ├── CreateFinancialAccountTool.php   (new - Step 0)
│   ├── UpdateFinancialAccountTool.php   (new - Step 0)
│   ├── CreatePayableTool.php            (new - Step 0)
│   ├── UpdatePayableTool.php            (new - Step 0)
│   ├── SaveUserTool.php
│   ├── UpdateRolePermissionsTool.php
│   └── UpdateSettingsTool.php
├── Resources/
│   ├── ClientResource.php
│   ├── ContractResource.php
│   ├── InvoiceResource.php
│   ├── SaleResource.php
│   ├── PurchaseResource.php
│   ├── DirectLessonResource.php
│   ├── PlanResource.php
│   ├── ModalityResource.php
│   ├── ProductResource.php
│   ├── ReceivableResource.php
│   ├── PayableResource.php
│   ├── MovementResource.php
│   ├── GatewayAccountResource.php
│   ├── ClientsListResource.php
│   ├── ContractsListResource.php
│   ├── InvoicesListResource.php
│   ├── SalesListResource.php
│   ├── PurchasesListResource.php
│   ├── ReceivablesListResource.php
│   ├── PayablesListResource.php
│   ├── OverdueReceivablesResource.php
│   └── MovementsByDateResource.php
└── Prompts/
    (empty for now)

routes/ai.php (published from laravel/mcp)

tests/Feature/
├── Actions/
│   ├── CreateCouponActionTest.php        (new - Step 0)
│   ├── UpdateCouponActionTest.php        (new - Step 0)
│   ├── CreateTrainerActionTest.php       (new - Step 0)
│   ├── UpdateTrainerActionTest.php       (new - Step 0)
│   ├── CreateSupplierActionTest.php      (new - Step 0)
│   ├── UpdateSupplierActionTest.php      (new - Step 0)
│   ├── CreateFinancialCategoryActionTest.php (new - Step 0)
│   ├── UpdateFinancialCategoryActionTest.php (new - Step 0)
│   ├── CreateCostCenterActionTest.php    (new - Step 0)
│   ├── UpdateCostCenterActionTest.php    (new - Step 0)
│   ├── CreatePlanCategoryActionTest.php  (new - Step 0)
│   ├── UpdatePlanCategoryActionTest.php  (new - Step 0)
│   ├── CreateFinancialAccountActionTest.php (new - Step 0)
│   ├── UpdateFinancialAccountActionTest.php (new - Step 0)
│   ├── CreatePayableActionTest.php       (new - Step 0)
│   └── UpdatePayableActionTest.php       (new - Step 0)
└── Mcp/
    ├── ToolTest.php
    ├── ResourceTest.php
    └── ServerRegistrationTest.php
```

---

## Step 7: Execution Order

| Order | Step | Files | Est. |
|---|---|---|---|
| **0.1** | Create 3 missing repositories | `app/Repositories/` (6 files), `AppServiceProvider` | 30 min |
| **0.2** | Create DTOs for 8 modules (24 DTO files) | `app/DTOs/{Module}/` | 1h |
| **0.3** | Create 16 Actions | `app/Actions/{Module}/` | 1.5h |
| **0.4** | Update 8 controllers to delegate | `app/Http/Controllers/` | 1h |
| **0.5** | Write Action tests (16 tests) | `tests/Feature/Actions/` | 1h |
| **0.6** | Run `pint --dirty` + `test --compact` | — | 5 min |
| **1.1** | Install `laravel/mcp`, publish routes | `composer.json`, `routes/ai.php` | 5 min |
| **1.2** | Create `GymnamiteServer` | `app/Mcp/Servers/GymnamiteServer.php` | 10 min |
| **2.1** | Create 38 MCP Tools | `app/Mcp/Tools/` | ~4h |
| **3.1** | Create 22 MCP Resources | `app/Mcp/Resources/` | ~2h |
| **4.1** | Register all in server | `GymnamiteServer.php` | 10 min |
| **5.1** | Write MCP tests | `tests/Feature/Mcp/` | ~1.5h |
| **6.1** | Run `pint --dirty` + `test --compact` | — | 5 min |

---

## Notes

- **Why no Prompts yet**: Prompts are templates for AI conversations. The tools and resources cover the functional surface. Prompts can be added later for guided workflows (e.g., "criar cliente com contrato").
- **Gateway webhooks** (`ProcessGatewayPostbackAction`) should NOT be exposed as MCP tools — they are triggered by external systems, not AI models.
- **`GenerateContractInvoicesAction`**, **`GenerateSaleInvoicesAction`**, etc. are internal sub-actions called by Create/Update actions. They should not be exposed as separate MCP tools.
- **Payable DTOs already exist** (`CreatePayableDTO`, `UpdatePayableDTO`) but are unused by the controller. Step 0 wires them in and creates the missing Action.
- **FinancialCategory, CostCenter, FinancialAccount** have no repository. These must be created before Actions can use DI.

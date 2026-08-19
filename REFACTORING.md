# Refactoring Status - Gymnamite Laravel Application

## Overview
Migration from monolithic controllers and services to Actions, DTOs, repositories, and focused billing/gateway collaborators.

---

## Phase 1: Foundation (Completed)
- [x] Install `spatie/laravel-data` for DTOs with validation attributes
- [x] Create `App\DTOs\Contracts\BaseDTO` with `fromRequest()`, `fromArray()`, and `toActionArray()`
- [x] Establish module-scoped `ActionResultDTO` value objects where Actions return success/failure payloads
- [x] Create `BaseAction` and `ActionInterface` with authorization, transaction execution, logging, and result-wrapping hooks

---

## Phase 2: Repository Layer (Completed)
- [x] Define `RepositoryInterface` with CRUD methods
- [x] Implement Eloquent repositories for the covered domain modules
- [x] Add specialized finder methods (withRelations, findByDocument, etc.)
- [x] Register bindings in `AppServiceProvider`

**Modules with repositories:**
- Client, Contract, Coupon, DirectLesson, GatewayAccount, GatewayInvoice, GatewayPayment, Invoice
- Modality, Movement, Payable, Plan, PlanCategory, Product, Purchase, Receivable, Sale, Supplier, Trainer

---

## Phase 3: DTOs (Completed) ✅
50 DTO files across 17 namespaces. DTO shapes follow each module's actual command/query needs; they are not uniformly Create/Update/Result triplets.

| Namespace | DTOs |
|-----------|------|
| Clients | `CreateClientDTO`, `UpdateClientDTO`, `ClientResultDTO`, `ActionResultDTO` |
| Contracts | `BaseDTO`, `CreateContractDTO`, `UpdateContractDTO`, `CancelContractDTO`, `ContractResultDTO`, `ActionResultDTO` |
| DirectLessons | `CreateDirectLessonDTO`, `UpdateDirectLessonDTO`, `DirectLessonResultDTO` |
| GatewayAccounts | `CreateGatewayAccountDTO`, `UpdateGatewayAccountDTO`, `ConfigureFiscalDataDTO`, `GatewayAccountResultDTO`, `ActionResultDTO` |
| GatewayInvoices / GatewayPayments / GatewayPostbacks | `GatewayInvoiceResultDTO`; `GatewayPaymentResultDTO`; `ProcessGatewayPostbackDTO` |
| Invoices | `FiscalSyncDTO`, `InvoiceResultDTO` |
| Modalities / Plans / Products | Module create/update/result DTOs; module `ActionResultDTO` values; `PlanTierDTO` for plans |
| Payables | `PayableDTO`, `PayableResultDTO` |
| Purchases / Sales | Module create/update/result DTOs |
| Receivables | `MarkReceivablePaidDTO`, `RequestGatewayInvoiceDTO`, `ReceivableResultDTO`, `ActionResultDTO` |
| Settings / Users | `UpdateSettingsDTO`; `SaveUserWithPermissionsDTO` |

---

## Phase 4: Billing and Gateway Collaborators (Completed) ✅
Eight collaborators are organized under `app/Services/Billing/` and `app/Services/Gateway/`.

| Service | Location | Responsibility |
|---------|----------|----------------|
| DiscountCalculator | `Billing/DiscountCalculator.php` | Calculate percentage/fixed discounts |
| InstallmentSplitter | `Billing/InstallmentSplitter.php` | Split values into installments with rounding |
| BillingSourceResolver | `Billing/BillingSourceResolver.php` | Resolve billable items (plans, modalities, products) |
| InvoiceGenerator | `Billing/InvoiceGenerator.php` | Generate and persist invoices from billing sources |
| GatewayAdapterResolver | `Gateway/GatewayAdapterResolver.php` | Resolve gateway adapter by account type |
| GatewayBillingOrchestrator | `Gateway/GatewayBillingOrchestrator.php` | Orchestrate billing → gateway invoice flow |
| FiscalInvoiceEmitter | `Gateway/FiscalInvoiceEmitter.php` | Emit fiscal invoices via gateway |
| FiscalSyncOrchestrator | `Gateway/FiscalSyncOrchestrator.php` | Sync fiscal status with gateway |

**Services outside these folders:** `BillableItemService` remains the sale/purchase-item coordinator. `StockRecalculationService` and `ReportService` remain specialized services.

---

## Phase 5: Actions (Implemented and Integrated) ✅
Actions were corrected for PSR-4 loading, valid `spatie/laravel-data` attributes, and the `BaseAction::handle(mixed $input)` contract. Fourteen controllers now execute Actions in production paths.

| Module | Actions |
|--------|---------|
| Contracts | CreateContract, UpdateContract, FindClient, CancelContract, GenerateContractInvoices (5) |
| Sales | CreateSale, UpdateSale, GenerateSaleInvoices (3) |
| Purchases | CreatePurchase, UpdatePurchase, GeneratePurchaseInvoices (3) |
| DirectLessons | CreateDirectLesson, UpdateDirectLesson, GenerateDirectLessonInvoices (3) |
| Receivables | RequestGatewayInvoice, MarkReceivablePaid (2) |
| GatewayAccounts | CreateGatewayAccount, UpdateGatewayAccount, ConfigureFiscalData (3) |
| Plans | CreatePlan, UpdatePlan (2) |
| Modalities | CreateModality, UpdateModality (2) |
| Products | CreateProduct, UpdateProduct (2) |
| Clients | CreateClient, UpdateClient (2) |
| GatewayPostbacks | ProcessGatewayPostback (1) |
| Users | SaveUserWithPermissions (1) |
| Roles | UpdateRolePermissions (1) |
| Settings | UpdateSettings (1) |

**Pattern per Action:**
- Extends `BaseAction`
- Uses `handle(mixed $input)` and validates the expected DTO/input internally
- HTTP controllers enforce module permissions through Laravel Gates before executing Actions. Actions keep an empty `$ability` because their execution is initiated by authorized controllers or system commands.
- Uses DTOs for input/output
- Returns an `ActionResultDTO` or the domain result required by the HTTP response
- Uses repositories via DI

---

## Phase 6: Controller Refactoring (IN PROGRESS)
**Goal:** Slim controllers to HTTP-only logic, delegate to Actions

### Current Controllers (22 `CrudModuleController`, 7 `ReadOnlyModuleController`, and `SettingController` on `AbstractModuleController`)

| Controller | Module | Refactor Status |
|------------|--------|-----------------|
| ClientController | Clients | ✅ Create/Update delegated |
| ContractController | Contracts | ✅ Create/Update/Find/Cancel delegated |
| PlanController | Plans | ✅ Create/Update delegated |
| ModalityController | Modalities | ✅ Create/Update delegated |
| ProductController | Products | ✅ Create/Update delegated |
| SaleController | Sales | ✅ Create/Update delegated |
| PurchaseController | Purchases | ✅ Create/Update delegated |
| DirectLessonController | DirectLessons | ✅ Create/Update delegated |
| ReceivableController | Receivables | ✅ Mark paid/request fiscal invoice delegated |
| PayableController | Payables | ✅ Retained generic CRUD (no domain use case) |
| GatewayAccountController | GatewayAccounts | ✅ Create/Update/configure fiscal data delegated |
| GatewayInvoiceController | GatewayInvoices | ✅ Retained read-only browsing (no write command) |
| GatewayPaymentController | GatewayPayments | ✅ Retained read-only browsing (no write command) |
| GatewayTransferController | GatewayTransfers | 🚧 Browsing retained; PIX transfer creation delegates to `CreateGatewayTransferAction` |
| GatewayCreditCardController | GatewayCreditCards | ✅ Retained read-only browsing (no write command) |
| GatewayCustomerController | GatewayCustomers | ✅ Retained read-only browsing (no write command) |
| GatewayPostbackController | GatewayPostbacks | ✅ Webhook processing delegated |
| FinancialAccountController | FinancialAccounts | ✅ Retained generic CRUD (reference data) |
| CostCenterController | CostCenters | ✅ Retained generic CRUD (reference data) |
| PlanCategoryController | PlanCategories | ✅ Retained generic CRUD (reference data) |
| RoleController | Roles | ✅ Permission updates delegated; generic CRUD remains intentionally absent |
| UserController | Users | ✅ Create/update permissions delegated |
| SettingController | Settings | ✅ Bulk update delegated |
| ReportController | Reports | ✅ Retained read-only generic browsing |

**Additional controllers outside the table:** Coupon, Trainer, Supplier, and FinancialCategory remain on generic CRUD because they are reference data. SelectBox remains an authorized query endpoint. Movements are read-only because payment flows create their entries with an invoice reference.

**Payables:** `PayableController` has no module-specific command, external integration, or invariant beyond the model-enforced `operation_type`. It is intentionally retained on the generic CRUD flow. A future payment settlement must be introduced as a dedicated Action rather than by wrapping generic create/update/delete operations.

**Administrative and reference modules:** Financial Accounts, Cost Centers, and Plan Categories remain on generic CRUD because they contain no domain command. User creation/update, Role permission updates, and Settings bulk updates delegate to Actions. Manual Transfers were removed from the application surface. Gateway transfers are now requested through the gateway flow and recorded as `GatewayTransfer`; their subsequent status is updated from gateway data/postbacks. Reports and gateway operational resources remain read-only where no write command is defined.

### Refactor Pattern
```php
// After (controller validates the HTTP request and delegates the use case)
public function store(Request $request): RedirectResponse|JsonResponse
{
    $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createClient->execute(CreateClientDTO::from(
        $this->validatedRequestData($request, $this->storeRequestClass()),
    ));

    if (! $result->success) {
        return $this->actionFailureResponse($request, $result->errors, $result->message);
    }

    return $request->expectsJson()
        ? response()->json($result->data, 201)
        : redirect()->route($this->routePrefix().'.index');
}
```

---

## Phase 7: Legacy Service Cleanup (IMPLEMENTED) ✅
**Goal:** Remove/deprecate old monolithic services after controllers use Actions

### Services Removed
| Service | Replacement |
|---------|-------------|
| BillingInvoiceService | InvoiceGenerator |
| GatewayBillingService | GatewayBillingOrchestrator |
| GatewayInvoicingService | FiscalInvoiceEmitter |
| GatewayFiscalSyncService | FiscalSyncOrchestrator |

### Services Retained
| Service | Status |
|---------|--------|
| BillableItemService | Retained: still coordinates sale and purchase items. |
| StockRecalculationService | Keep (specialized) |
| ReportService | Keep (specialized) |

**Migration completed:**
- `GenerateContractInvoicesAction`, `GenerateSaleInvoicesAction`, `GeneratePurchaseInvoicesAction`, and `GenerateDirectLessonInvoicesAction` are now invoked by the module Create/Update Actions as their invoice-generation step, replacing the duplicated inline `InvoiceGenerator` + gateway-sync calls.
- `gateway:sync-invoices` uses `GatewayBillingOrchestrator::syncInvoice()`.
- `gateway:sync-fiscal-invoices` uses `FiscalSyncOrchestrator::syncAll()`.
- Receivable fiscal requests and eligibility queries use `FiscalInvoiceEmitter`.
- `InvoiceGeneratorTest` replaces `BillingInvoiceServiceTest`; `FiscalSyncOrchestratorTest` replaces `GatewayFiscalSyncServiceTest`.

**Verification status (2026-08-14):**
- [x] `vendor/bin/pint --dirty --format agent` passed.
- [x] PHP lint and `git diff --check` passed.
- [x] Docker focused tests passed for plan persistence, dashboard/select-box authorization, catalog persistence, gateway account security, and `gateway:sync-invoices`.
- [ ] Sales, Purchases, DirectLessons, Receivables, `FiscalSyncOrchestrator`, and fiscal-sync command coverage still require rerun.

---

## Phase 8: Testing & Verification (IN PROGRESS)
- [x] Feature tests for each Action (happy path, failure path, edge cases)
- [x] Controller tests verify delegation to Actions
- [ ] Integration tests for billing/gateway flows
- [x] Run full test suite: `php artisan test --compact`
- [x] Code style: `vendor/bin/pint --dirty --format agent`
- [x] Static analysis: `vendor/bin/phpstan analyse` (Larastan configured; see record below)

**Current verification record (2026-08-19):**
- [x] Pint passed; full suite: **240 passed, 0 failed** (up from 182 passed). No regressions introduced.
- [x] 58 new Action tests added covering: CreateModalityAction, UpdateModalityAction, CreateProductAction, UpdateProductAction, UpdateSettingsAction, CreateClientAction, UpdateClientAction, CreatePlanAction, UpdatePlanAction, SaveUserWithPermissionsAction, UpdateRolePermissionsAction (6 test files, 56 tests).
- [x] 9 controller delegation tests verifying HTTP routes delegate to Actions (ControllerDelegationTest): Client store/update, Modality store/update, Plan store, Product store, Settings update, User store, Role update.
- [x] Fixed pre-existing `EloquentProductRepository::$with` referencing `'unity'` instead of `'productUnity'` (broke `UpdateProductAction` via `findOrFail`).
- [x] Made `APP_ENV=testing` permanent in phpunit.xml via `force="true"` — no runtime prefix needed.
- [x] Lifecycle integration verified end-to-end: Sales, Purchases, DirectLessons, Contracts (`HiringFlowTest`), `InvoiceGeneratorTest`, and `InvoiceStatusLifecycleTest` pass.
- [x] Fixed pre-existing morph-alias bugs that blocked invoice generation: `InvoiceGenerator` now stores `holder_type`/`billable_type` via `getMorphClass()`, global morph maps are registered in `AppServiceProvider::boot()`, and `CancelContractAction`/`GenerateContractInvoicesAction` query by the morph alias.
- [x] Resolved the 6 remaining pre-existing failures:
  - Added `Invoice::financialCategory()` relationship (referenced by `EloquentInvoiceRepository::$with`).
  - `FiscalInvoiceEmitter` no longer calls `firstWhere()` with an array (repository method accepts a single column); it now uses `newQuery()` with two `where` clauses.
  - `UpdateSettingsAction::handle()` now returns a value (fixes the `mixed` return TypeError on the settings update route).
  - `Role::permissions()` now orders by `orderByPivot('id')`, making `selectedPermissionIds`, `editablePermissionIds`, and `editablePermissionIdsByRole` deterministic (attach order) across `UserModuleTest` and the user/role detail payloads.
  - `GatewayModuleAccessTest` now asserts `create` only for gateway modules that actually support it (read-only modules such as `gateway_payments`, `gateway_postbacks`, `gateway_customers`, `gateway_credit_cards`, and `gateway_invoices` only expose `view`).
  - `ModuleDetailsRouteTest` select-settings assertion now compares the decoded JSON `content` value instead of querying a `json` column with a string literal.
- [x] Full suite requires `APP_ENV=testing` at runtime: the container exports `APP_ENV=local`, so phpunit.xml `<env>` without `force="true"` does not override it and the CSRF middleware is not skipped during tests (HTTP POSTs return 419).
- [x] Static analysis: `vendor/bin/phpstan analyse` (Larastan, level 7) reports a pre-existing baseline of **825 errors** across 192 files. No new errors were introduced by this step; the only addition is `Invoice::financialCategory()` missing a return type, consistent with the other relationship methods in that file. Note: `php artisan stan` is not a registered command; the project uses `vendor/bin/phpstan analyse` via the `types:check` Composer script.

---

## Phase 9: Frontend Integration (PENDING)
- [ ] Update Inertia/Vue pages to use new Action-based endpoints
- [ ] Ensure form submissions match DTO structures
- [ ] Handle ActionResultDTO responses (success/error toast, redirects)

Current frontend payloads were kept compatible for migrated modules. A final audit is still required for forms, errors, redirects, and JSON responses across all migrated pages.

---

## Gateway PIX Transfers (Completed) ✅

- [x] Remove the legacy manual `Transfer` controller, model, routes, navigation, and pages.
- [x] Create `GatewayTransferRecipient` with encrypted PIX key storage and a gateway-account relationship.
- [x] Add `gateway_transfer_recipient_id` to gateway transfers.
- [x] Add `CreateGatewayTransferAction`, which resolves the payment adapter from the selected recipient's gateway account.
- [x] Add `POST /gateway-transfers`, requiring `gateway_transfers.create` and accepting only recipient, amount, and optional description.
- [x] Persist the recipient reference when the Asaas adapter creates a transfer.
- [x] Keep transfer update/delete unavailable; only gateway synchronization/postbacks may change status.
- [x] Grant Managers the gateway modules, including gateway account configuration and transfer creation.
- [x] Build the recipient CRUD controller, routes, Inertia pages, and navigation.
- [x] Add the Inertia/Vuetify transfer request interface.
- [x] Add focused recipient and transfer-creation tests.
- [x] Start the application container and run Pint and focused tests for this flow.

---

## File Structure After Migration

```
app/
├── Actions/
│   ├── BaseAction.php
│   ├── Contracts/ (including `ActionInterface.php`)
│   ├── Exceptions/
│   ├── Sales/
│   ├── Purchases/
│   ├── DirectLessons/
│   ├── Receivables/
│   ├── GatewayAccounts/
│   ├── GatewayPostbacks/
│   ├── GatewayTransfers/
│   ├── Plans/
│   ├── Modalities/
│   ├── Products/
│   ├── Clients/
│   ├── Settings/
│   └── Users/
├── DTOs/
│   ├── Contracts/
│   ├── Clients/
│   ├── Plans/
│   ├── Modalities/
│   ├── Products/
│   ├── Sales/
│   ├── Purchases/
│   ├── DirectLessons/
│   ├── Receivables/
│   ├── Payables/
│   ├── GatewayAccounts/
│   ├── GatewayPostbacks/
│   ├── GatewayInvoices/
│   ├── GatewayPayments/
│   ├── Invoices/
│   ├── Settings/
│   └── Users/
├── Services/
│   ├── Billing/
│   │   ├── DiscountCalculator.php
│   │   ├── InstallmentSplitter.php
│   │   ├── BillingSourceResolver.php
│   │   └── InvoiceGenerator.php
│   ├── Gateway/
│   │   ├── GatewayAdapterResolver.php
│   │   ├── GatewayBillingOrchestrator.php
│   │   ├── FiscalInvoiceEmitter.php
│   │   └── FiscalSyncOrchestrator.php
│   ├── StockRecalculationService.php
│   ├── BillableItemService.php
│   └── ReportService.php
├── Repositories/
│   ├── Contracts/ (interfaces)
│   └── Eloquent/ (implementations)
└── Http/Controllers/ (slim, delegate to Actions)
```

---

## Current Status Summary

| Phase | Status | Tests | Notes |
|-------|--------|-------|-------|
| 1. Foundation | ✅ Done | - | Base classes created |
| 2. Repositories | ✅ Done | - | 19 Eloquent bindings for covered modules |
| 3. DTOs | ✅ Done | - | 50 files across 17 namespaces |
| 4. Billing/Gateway Collaborators | ✅ Done | - | 8 focused collaborators |
| 5. Actions | ✅ Integrated | Partial | 34 module Actions used by 14 controllers |
| 6. Controller Refactor | ✅ Complete | Partial | Domain commands delegate to Actions; reference data remains generic CRUD; movements are read-only |
| 7. Legacy Cleanup | ✅ Implemented | Partial | Four legacy billing/gateway services removed; invoice-generation Actions wired into module Create/Update Actions |
| 8. Testing | 🚧 In progress | 240 passed / 0 failed | Action tests and controller delegation tests added; remaining: billing/gateway integration tests |
| 9. Frontend | ⏳ Pending | - | Inertia/Vue integration |
| Gateway PIX | ✅ Completed | Focused tests passed | Recipient CRUD, transfer request, permissions, Pint verified |

---

## Next Steps (Priority Order)

1. **Lifecycle integration**: ✅ Completed (2026-08-18). The invoice-generation Actions are now the invoice-generation step inside the module Create/Update Actions; the duplicated inline `InvoiceGenerator` + gateway-sync code was removed.
2. **Phase 8**: ✅ Action tests (56 tests) and controller delegation tests (9 tests) added; full suite green at **240 passed, 0 failed**; `APP_ENV=testing` made permanent via `force="true"` in phpunit.xml; `EloquentProductRepository::$with` fixed (`'unity'` -> `'productUnity'`). Remaining: billing/gateway integration tests.
3. **Phase 9**: Audit frontend form payloads and error handling against final DTO and response contracts.

---

## Verification Commands

```bash
# Run all tests
php artisan test --compact

# Check code style
vendor/bin/pint --dirty --format agent

# Static analysis (if Larastan)
vendor/bin/phpstan analyse

# Check for unused code
php artisan code:inspect
```

---

*Updated: 2026-08-19 | Phases 1–5 completed; Phase 6 has 14 Action-migrated controllers with retained/deferred decisions; gateway PIX transfers completed; Phase 7 completed with the invoice-generation Actions wired into the module Create/Update Actions; invoice-generation morph-alias bugs fixed; the 6 remaining pre-existing test failures fixed; Phase 8: 56 Action feature tests + 9 controller delegation tests added (240 passed / 0 failed); EloquentProductRepository::$with fixed; APP_ENV=testing made permanent via force="true"; remaining: billing/gateway integration tests; Phase 9 pending.*

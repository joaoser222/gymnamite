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
Actions were corrected for PSR-4 loading, valid `spatie/laravel-data` attributes, and the `BaseAction::handle(mixed $input)` contract. Thirteen controllers now execute Actions in production paths.

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
| Settings | UpdateSettings (1) |

**Pattern per Action:**
- Extends `BaseAction`
- Uses `handle(mixed $input)` and validates the expected DTO/input internally
- HTTP controllers enforce the existing module permission before executing an Action. Actions use an empty `$ability` until the custom permission system is integrated with Laravel Gates/Policies.
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
| GatewayTransferController | GatewayTransfers | ✅ Retained read-only browsing (no write command) |
| GatewayCreditCardController | GatewayCreditCards | ✅ Retained read-only browsing (no write command) |
| GatewayCustomerController | GatewayCustomers | ✅ Retained read-only browsing (no write command) |
| GatewayPostbackController | GatewayPostbacks | ✅ Webhook processing delegated |
| FinancialAccountController | FinancialAccounts | ✅ Retained generic CRUD (reference data) |
| CostCenterController | CostCenters | ✅ Retained generic CRUD (reference data) |
| PlanCategoryController | PlanCategories | ✅ Retained generic CRUD (reference data) |
| RoleController | Roles | ⚠️ Deferred: generic CRUD is incompatible with the role schema |
| UserController | Users | ✅ Create/update permissions delegated |
| SettingController | Settings | ✅ Bulk update delegated |
| TransferController | Transfers | ⚠️ Deferred: transfer semantics are not defined |
| ReportController | Reports | ✅ Retained read-only generic browsing |

**Additional controllers outside the table:** Coupon, Trainer, Supplier, FinancialCategory, Movement, and SelectBox are not Action-migrated. Their action-vs-generic-CRUD treatment remains deferred until a domain command is identified.

**Payables:** `PayableController` has no module-specific command, external integration, or invariant beyond the model-enforced `operation_type`. It is intentionally retained on the generic CRUD flow. A future payment settlement must be introduced as a dedicated Action rather than by wrapping generic create/update/delete operations.

**Administrative and reference modules:** Financial Accounts, Cost Centers, and Plan Categories remain on generic CRUD because they contain no domain command. User creation/update and Settings bulk updates delegate to Actions. Roles and Transfers require lifecycle and schema decisions before their generic CRUD flows can be refactored safely. Reports and gateway operational resources remain read-only where no write command is defined.

### Refactor Pattern
```php
// After (controller validates the HTTP request and delegates the use case)
public function store(Request $request): RedirectResponse|JsonResponse
{
    $this->authorizeAccess(AccessAction::CREATE);

    $result = $this->createClient->execute(CreateClientDTO::fromArray(
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
- `GenerateSaleInvoicesAction`, `GeneratePurchaseInvoicesAction`, and `GenerateDirectLessonInvoicesAction` inject `InvoiceGenerator`; gateway sync queuing remains unchanged.
- `gateway:sync-invoices` uses `GatewayBillingOrchestrator::syncInvoice()`.
- `gateway:sync-fiscal-invoices` uses `FiscalSyncOrchestrator::syncAll()`.
- Receivable fiscal requests and eligibility queries use `FiscalInvoiceEmitter`.
- `InvoiceGeneratorTest` replaces `BillingInvoiceServiceTest`; `FiscalSyncOrchestratorTest` replaces `GatewayFiscalSyncServiceTest`.

**Verification status (2026-08-06):**
- [x] `vendor/bin/pint --dirty --format agent` passed.
- [x] PHP lint and `git diff --check` passed.
- [ ] Focused feature tests did not complete: PHPUnit was terminated by resource constraints before assertions ran in the Sales, Purchases, DirectLessons, Receivables, `FiscalSyncOrchestrator`, and fiscal-sync command coverage.

---

## Phase 8: Testing & Verification (IN PROGRESS)
- [ ] Feature tests for each Action (happy path, failure path, edge cases)
- [ ] Controller tests verify delegation to Actions
- [ ] Integration tests for billing/gateway flows
- [ ] Run full test suite: `php artisan test --compact`
- [x] Code style: `vendor/bin/pint --dirty --format agent`
- [ ] Static analysis: `php artisan stan` (if Larastan configured)

**Current verification record:**
- [x] Pint, PHP lint, and `git diff --check` passed.
- [ ] Focused feature coverage must be rerun in an environment with sufficient resources; PHPUnit was interrupted before assertions, so no focused feature-test result is recorded as passing.

---

## Phase 9: Frontend Integration (PENDING)
- [ ] Update Inertia/Vue pages to use new Action-based endpoints
- [ ] Ensure form submissions match DTO structures
- [ ] Handle ActionResultDTO responses (success/error toast, redirects)

Current frontend payloads were kept compatible for migrated modules. A final audit is still required for forms, errors, redirects, and JSON responses across all migrated pages.

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
| 5. Actions | ✅ Integrated | Partial | Actions corrected and used by 13 controllers |
| 6. Controller Refactor | 🚧 In progress | Partial | 13 controllers migrated; role, transfer, and additional controllers remain pending lifecycle decisions |
| 7. Legacy Cleanup | ✅ Implemented | Focused tests incomplete | Four legacy billing/gateway services removed; PHPUnit resource constraints interrupted focused tests before assertions |
| 8. Testing | 🚧 In progress | Focused tests incomplete | Re-run focused feature coverage, then full suite and static analysis in a sufficiently provisioned environment |
| 9. Frontend | ⏳ Pending | - | Inertia/Vue integration |

---

## Next Steps (Priority Order)

1. **Phase 6**: Define the Role lifecycle and Transfer semantics before refactoring their generic CRUD flows.
2. **Phase 6**: Decide the treatment for Coupon, Trainer, Supplier, FinancialCategory, Movement, and SelectBox.
3. **Authorization**: Integrate the custom module permission system with Laravel Gates/Policies, or formally retain controller-level authorization as the Action standard.
4. **Phase 8**: Resolve the PHPUnit resource termination, rerun the interrupted focused feature tests, add missing Action/controller delegation and gateway integration tests, then run the full suite and static analysis.
5. **Phase 9**: Audit frontend form payloads and error handling against final DTO and response contracts.

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

*Updated: 2026-08-06 | Phases 1–5 completed; Phase 6 has 13 Action-migrated controllers with retained/deferred controller decisions; Phase 7 completed; Phase 8 focused feature tests remain interrupted by resource constraints before assertions; Phase 9 pending.*

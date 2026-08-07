# Refactoring Plan - Gymnamite Laravel Application

## Overview
Migration from monolithic controllers/services to clean architecture with DTOs, Domain Services, and Actions.

---

## Phase 1: Foundation (Completed)
- [x] Install `spatie/laravel-data` for DTOs with validation attributes
- [x] Create BaseDTO with `fromRequest()`, `fromArray()`, `toActionArray()`
- [x] Define `ActionResultDTO` pattern (success/failure with data, message, errors)
- [x] Create `BaseAction` with authorization, validation, and transaction handling

---

## Phase 2: Repository Layer (Completed)
- [x] Define `RepositoryInterface` with CRUD methods
- [x] Implement Eloquent repositories for all modules
- [x] Add specialized finder methods (withRelations, findByDocument, etc.)
- [x] Register bindings in `AppServiceProvider`

**Modules with repositories:**
- Client, Contract, Plan, Modality, Product, Sale, Purchase
- DirectLesson, Receivable, Payable, GatewayAccount, GatewayInvoice
- Trainer, Coupon, Supplier, PlanCategory, CostCenter, FinancialAccount

---

## Phase 3: DTOs (Completed) ✅
28 DTO files across 14 modules (Create/Update/Result DTOs per module)

| Module | Create DTO | Update DTO | Result DTO | ActionResultDTO |
|--------|------------|------------|------------|-----------------|
| Clients | ✅ | ✅ | ✅ | ✅ |
| Contracts | ✅ | ✅ | ✅ | ✅ |
| Plans | ✅ | ✅ | ✅ | ✅ |
| Modalities | ✅ | ✅ | ✅ | ✅ |
| Products | ✅ | ✅ | ✅ | ✅ |
| Sales | ✅ | ✅ | ✅ | ✅ |
| Purchases | ✅ | ✅ | ✅ | ✅ |
| DirectLessons | ✅ | ✅ | ✅ | ✅ |
| Receivables | ✅ | ✅ | ✅ | ✅ |
| Payables | ✅ | ✅ | ✅ | ✅ |
| GatewayAccounts | ✅ | ✅ | ✅ | ✅ |
| GatewayInvoices | - | - | ✅ | - |
| Invoices (Fiscal) | ✅ | ✅ | ✅ | - |
| GatewayCustomers | ✅ | ✅ | ✅ | - |

---

## Phase 4: Domain Services - Pure Logic (Completed) ✅
8 new services in `app/Services/Billing/` and `app/Services/Gateway/`

| Service | Location | Responsibility |
|---------|----------|----------------|
| DiscountCalculator | `Billing/DiscountCalculator.php` | Calculate percentage/fixed discounts |
| InstallmentSplitter | `Billing/InstallmentSplitter.php` | Split values into installments with rounding |
| BillingSourceResolver | `Billing/BillingSourceResolver.php` | Resolve billable items (plans, modalities, products) |
| InvoiceGenerator | `Billing/InvoiceGenerator.php` | Generate invoice data arrays from billing sources |
| GatewayAdapterResolver | `Gateway/GatewayAdapterResolver.php` | Resolve gateway adapter by account type |
| GatewayBillingOrchestrator | `Gateway/GatewayBillingOrchestrator.php` | Orchestrate billing → gateway invoice flow |
| FiscalInvoiceEmitter | `Gateway/FiscalInvoiceEmitter.php` | Emit fiscal invoices via gateway |
| FiscalSyncOrchestrator | `Gateway/FiscalSyncOrchestrator.php` | Sync fiscal status with gateway |

**Legacy services retained after Phase 7:**
- BillableItemService, StockRecalculationService, and ReportService

---

## Phase 5: Actions (Implemented and Integrated) ✅
Actions were corrected for PSR-4 loading, valid `spatie/laravel-data` attributes, and the `BaseAction::handle(mixed $input)` contract. Thirteen controllers now execute Actions in production paths.

| Module | Actions |
|--------|---------|
| Contracts | CreateContract, UpdateContract, FindClient, CancelContract, GenerateContractInvoices (5) |
| Sales | CreateSale, UpdateSale, ProcessSalePayment (3) |
| Purchases | CreatePurchase, UpdatePurchase, GeneratePurchaseInvoices (3) |
| DirectLessons | CreateDirectLesson, UpdateDirectLesson, CompleteDirectLesson (3) |
| Receivables | RequestGatewayInvoice, MarkReceivablePaid (2) |
| GatewayAccounts | CreateGatewayAccount, UpdateGatewayAccount, ConfigureFiscalData (3) |
| Plans | CreatePlan, UpdatePlan (2) |
| Modalities | UpdateModality (1) |
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

### Current Controllers (22 CrudModuleController + 5 ReadOnlyModuleController)

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
| GatewayInvoiceController | GatewayInvoices | ⏳ Pending |
| GatewayPaymentController | GatewayPayments | ⏳ Pending |
| GatewayTransferController | GatewayTransfers | ⏳ Pending |
| GatewayCreditCardController | GatewayCreditCards | ⏳ Pending |
| GatewayCustomerController | GatewayCustomers | ⏳ Pending |
| GatewayPostbackController | GatewayPostbacks | ✅ Webhook processing delegated |
| FinancialAccountController | FinancialAccounts | ✅ Retained generic CRUD (reference data) |
| CostCenterController | CostCenters | ✅ Retained generic CRUD (reference data) |
| PlanCategoryController | PlanCategories | ✅ Retained generic CRUD (reference data) |
| RoleController | Roles | ⚠️ Deferred: generic CRUD is incompatible with the role schema |
| UserController | Users | ✅ Create/update permissions delegated |
| SettingController | Settings | ✅ Bulk update delegated |
| TransferController | Transfers | ⚠️ Deferred: transfer semantics are not defined |
| ReportController | Reports | ✅ Retained read-only generic browsing |

**Additional controllers outside the original table:** Coupon, Trainer, Supplier, FinancialCategory, Movement, and SelectBox also require an explicit decision: create Actions where they have business behavior, or retain the generic module controller where CRUD is sufficient.

**Payables:** `PayableController` has no module-specific command, external integration, or invariant beyond the model-enforced `operation_type`. It is intentionally retained on the generic CRUD flow. A future payment settlement must be introduced as a dedicated Action rather than by wrapping generic create/update/delete operations.

**Administrative and reference modules:** Financial Accounts, Cost Centers, and Plan Categories remain on generic CRUD because they contain no domain command. User creation/update and Settings bulk updates delegate to Actions. Roles and Transfers require lifecycle and schema decisions before their generic CRUD flows can be refactored safely. Reports remain read-only until execution requirements are defined.

### Refactor Pattern
```php
// Before (monolithic)
public function store(StoreClientRequest $request) {
    // 100+ lines: validation, business logic, gateway calls, responses
}

// After (delegated to Action)
public function store(StoreClientRequest $request) {
    $dto = CreateClientDTO::fromRequest($request);
    $result = app(CreateClientAction::class)->execute($dto);

    return $result->success
        ? redirect()->route('clients.show', $result->data->id)->with('success', $result->message)
        : back()->withErrors($result->errors ?? [$result->message])->withInput();
}
```

---

## Phase 7: Legacy Service Cleanup (IMPLEMENTED)
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
- Sales, Purchases, and DirectLessons Actions now inject `InvoiceGenerator`; gateway sync queuing remains unchanged.
- `gateway:sync-invoices` uses `GatewayBillingOrchestrator::syncInvoice()`.
- `gateway:sync-fiscal-invoices` uses `FiscalSyncOrchestrator::syncAll()`.
- Receivable fiscal requests and eligibility queries use `FiscalInvoiceEmitter`.
- `FiscalSyncOrchestratorTest` replaces `GatewayFiscalSyncServiceTest`; billing coverage now resolves `InvoiceGenerator`.

**Verification status (2026-08-06):**
- [x] `vendor/bin/pint --dirty --format agent` passed.
- [ ] Focused feature tests could not complete: PHPUnit terminated the PHP process prematurely before executing assertions in Sales, Purchases, DirectLessons, Receivables, FiscalSyncOrchestrator, and fiscal sync command tests.

---

## Phase 8: Testing & Verification (IN PROGRESS)
- [ ] Feature tests for each Action (happy path, failure path, edge cases)
- [ ] Controller tests verify delegation to Actions
- [ ] Integration tests for billing/gateway flows
- [ ] Run full test suite: `php artisan test --compact`
- [x] Code style: `vendor/bin/pint --dirty --format agent`
- [ ] Static analysis: `php artisan stan` (if Larastan configured)

**Verification completed during Phase 6:**
- [x] Pint, PHP lint, class-load checks, route checks, and `git diff --check` for each migrated block.
- [x] Sales, Purchases, and DirectLessons focused tests: 17 tests, 105 assertions.
- [x] Test database connection and `migrate:fresh` validation completed after normalizing `.env.testing` to `127.0.0.1`.
- [x] Unit tests independent of the database: 3 tests, 6 assertions (`ReportServiceTest`).
- [ ] Feature tests for Receivables, Gateway Accounts, Gateway Postbacks, Users, and Settings need rerun: the PHPUnit process is intermittently terminated with signal 9 or exceeds the resource limit.

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
│   ├── Contracts/
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
│   ├── Invoices/
│   ├── GatewayCustomers/
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
| 2. Repositories | ✅ Done | - | All modules covered |
| 3. DTOs | ✅ Done | - | 28 files, validation attributes |
| 4. Domain Services | ✅ Done | - | 8 new pure-logic services |
| 5. Actions | ✅ Integrated | Partial | Actions corrected and used by 13 controllers |
| 6. Controller Refactor | 🚧 In progress | Partial | 13 controllers migrated; role, transfer, and additional controllers remain pending lifecycle decisions |
| 7. Legacy Cleanup | ✅ Implemented | Focused tests blocked | Four legacy billing/gateway services removed; PHPUnit resource/process termination prevented focused test completion |
| 8. Testing | 🚧 In progress | 20 pass / 111 assertions | Database connectivity/migrations and unit tests pass; feature-suite execution remains resource-constrained |
| 9. Frontend | ⏳ Pending | - | Inertia/Vue integration |

---

## Next Steps (Priority Order)

1. **Phase 6**: Define the Role lifecycle and Transfer semantics before refactoring their generic CRUD flows.
2. **Phase 6**: Decide the treatment for Coupon, Trainer, Supplier, FinancialCategory, Movement, and SelectBox.
3. **Authorization**: Integrate the custom module permission system with Laravel Gates/Policies, or formally retain controller-level authorization as the Action standard.
4. **Phase 8**: Resolve the PHPUnit resource termination, add Action/controller delegation and gateway integration tests, then run the full suite and static analysis.
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

*Updated: 2026-08-06 | Phase 6 in progress: 13 controllers migrated; Phase 7 implemented; Phase 8 verification remains constrained by PHPUnit process termination; Phase 9 pending*

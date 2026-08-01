---
name: commander
description: >
  Executes structured implementation plans in this Laravel + Inertia/Vue + Vuetify project.
  Use this skill when a plan has been created and needs to be carried out — it reads the
  plan steps, implements backend/frontend/test changes following project conventions,
  and reports results.
---
# Gymnamite Commander

You are the executor for the Gymnamite project. Your role is to receive structured
implementation plans and carry them out methodically, following project conventions.

**Communication:** Speak to the user in Portuguese. All internal reasoning and
implementation logic is in English.

## Workflow

### 1. Receive the Plan

Accept a plan in the standard format (produced by the planner agent or provided
directly). Acknowledge receipt and confirm you understand the scope before starting.

### 2. For Each Step, Follow This Sequence:

**A. Inspect existing code** — Before creating anything, check sibling files for
    conventions. Read at least one existing example of the pattern you are about
    to implement (e.g., if creating a CRUD module, read a similar controller).

**B. Load the relevant skill** — Activate the skill(s) identified in the plan step.
    Follow the skill's guidance for patterns, naming, and structure.

**C. Implement** — Create or modify files. Use `php artisan make:*` where possible.

**D. Verify formatting** — After each PHP change, run:
    ```
    vendor/bin/pint --dirty --format agent
    ```

**E. Run focused tests** — After implementing a feature area, run the relevant test:
    ```
    php artisan test --compact --filter=<test>
    ```

**F. Apply the DRY principle** — Always reuse existing code instead of duplicating it.
    Before writing any logic, check whether the project already provides it:
    - If a base or abstract class already implements a function, extend or reuse that
      class instead of reimplementing the behavior (e.g., prefer
      CrudModuleController/ReadOnlyModuleController/AbstractModuleController over
      writing module logic from scratch, and reuse existing services and helpers).
    - If you observe the same function being repeated across classes, extract it into
      a trait (or a shared service/helper) and have those classes use it — avoid
      copy-pasting logic between files.
    - Prefer composition and project conventions over introducing new abstractions
      that duplicate what already exists. In every review of a change, look for
      duplicated logic and consolidate it before finalizing.

### 3. Per-Domain Implementation Notes

**Backend (Laravel):**
- Generate models with `php artisan make:model -mfs` (migration, factory, seeder)
- Controllers extend CrudModuleController, ReadOnlyModuleController, or AbstractModuleController
- Define accessModule() returning an AccessModule enum case
- Use Form Request classes for validation (storeRequestClass/updateRequestClass hooks)
- Register routes with Route::module() for CRUD, manual index/show for read-only
- Services go in app/Services/, reports in app/Reports/Definitions/
- Update RolePermissionMap and run access-control:sync for new permissions

**Frontend (Vue/Inertia):**
- Pages in resources/js/pages/<snake_case_module>/
- Use TablePage.vue for index lists, DetailsPage.vue for CRUD forms,
  ReadOnlyDetailsPage.vue for read-only detail views
- Pass module prop matching the permission module name
- Format values with formatDate, formatCurrency, findLabel helpers
- Status chips: :color="findOption(options, value)?.color" with variant="tonal"
- Icons use Tabler names (ti ti-plus, ti ti-pencil, etc.)
- Add menu entries in resources/js/layouts/AuthenticatedLayout.vue

**Testing:**
- PHPUnit classes (not Pest)
- Feature tests for routes, permissions, validation, persistence
- Unit tests for pure logic, value objects, adapters
- Use factories with existing factory states
- Run: php artisan test --compact --filter=<test>

**Gateway Integration:**
- Definition in app/PaymentGateways/Definitions/
- Adapter in app/PaymentGateways/Adapters/ implements PaymentGatewayAdapter
- Register in PaymentGatewayManager constructor
- Bind in AppServiceProvider
- Read-only routes in routes/web.php
- Postback handler in GatewayPostbackController

### 4. Error Handling

If a step fails (command error, test failure, unexpected file structure):
- Stop and report the error to the user
- Explain what went wrong and suggest a fix
- Do not continue with subsequent steps until resolved

### 5. Completion

After all steps are done:
- Run `vendor/bin/pint --dirty --format agent` one final time
- Run the full test suite for the affected area
- Present a summary to the user:
  - What was created/modified
  - Test results
  - Any pending manual steps (e.g., run migrations, access-control:sync)

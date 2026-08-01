---
name: asaas-payment-gateway
description: >-
  Guides implementation and maintenance of the Asaas payment gateway integration in this Laravel project.
  Use this skill when the user asks about Asaas, payment gateway, gateway adapter, gateway definition,
  webhooks/postbacks, gateway billing sync, gateway accounts, gateway payments, gateway transfers,
  gateway customers, gateway credit cards, Asaas API integration, processing postbacks from Asaas,
  creating or modifying payment gateway code, registering webhooks in Asaas, tokenizing credit cards,
  creating customers on Asaas, or any task involving the app/PaymentGateways/ directory.
  Also use it when working with GatewayBillingService, SyncGatewayInvoices command, or the gateway account
  configuration pages in the frontend.
---

# Asaas Payment Gateway

This project integrates [Asaas](https://asaas.com) as its payment gateway provider. The gateway infrastructure is under `app/PaymentGateways/` and follows an adapter/contract pattern designed to support multiple providers.

## Architecture Overview

```
app/PaymentGateways/
├── Contracts/
│   └── PaymentGatewayAdapter.php     # Interface all gateways must implement
├── Definitions/
│   ├── PaymentGatewayDefinition.php  # Abstract base for provider metadata & settings
│   ├── PaymentGatewaySettingDefinition.php  # DTO for individual config fields
│   └── AsaasPaymentGatewayDefinition.php  # Asaas-specific config definition
├── Adapters/
│   └── AsaasPaymentGatewayAdapter.php  # Asaas API client (695 lines)
└── PaymentGatewayManager.php         # Registry of gateway definitions
```

Supporting files:
- `app/Services/GatewayBillingService.php` — Coordinates invoice billing with the gateway
- `app/Http/Controllers/GatewayPostbackController.php` — Webhook receive endpoint
- `app/Http/Controllers/GatewayAccountController.php` — CRUD for gateway accounts (extends `CrudModuleController`)
- `app/Http/Requests/GatewayAccountRequest.php` — Handles password field preservation
- `app/Console/Commands/SyncGatewayInvoices.php` — Artisan command `gateway:sync-invoices`
- `app/Providers/AppServiceProvider.php` — Binds `PaymentGatewayAdapter` and `PaymentGatewayManager`
- `app/AccessControl/AccessModule.php` — Defines `GATEWAY_*` module cases with view-only access
- `app/Enums/Gateway/TransactionStatus.php` — `PENDING`, `PAID`, `FAILED`, `REFUNDED`, `CANCELED`, `OVERDUE`
- `app/Enums/Gateway/PostbackStatus.php` — `PENDING`, `FAILED`, `SUCCESS`

Frontend pages under `resources/js/pages/gateway_*/` (all read-only except `gateway_accounts`):
- `gateway_accounts/Details.vue` — Dynamic settings form based on `PaymentGatewayDefinition`
- `gateway_accounts/Index.vue` — Table listing
- `gateway_payments/`, `gateway_transfers/`, `gateway_postbacks/`, `gateway_customers/`, `gateway_credit_cards/` — Read-only list/detail pages

Routes (in `routes/web.php`):
- Gateway accounts use `Route::module(GatewayAccountController::class)` (full CRUD under Avançado)
- Read-only gateway modules register `index` and `show` manually under Gateway de Pagamentos
- `POST /gateway-postbacks/{gateway_account}/receive` — Public webhook endpoint (no auth middleware)
- `GET /gateway-payments`, `GET /gateway-payments/{gateway_payment}` — Read-only
- `GET /gateway-transfers`, `GET /gateway-transfers/{gateway_transfer}` — Read-only
- `GET /gateway-postbacks`, `GET /gateway-postbacks/{gateway_postback}` — Read-only
- `GET /gateway-customers`, `GET /gateway-customers/{gateway_customer}` — Read-only
- `GET /gateway-credit-cards`, `GET /gateway-credit-cards/{gateway_credit_card}` — Read-only

## Adding a New Payment Gateway Provider

### 1. Create the Definition class

Extend `PaymentGatewayDefinition` in `app/PaymentGateways/Definitions/`:

```php
class YourGatewayDefinition extends PaymentGatewayDefinition
{
    public function name(): string
    {
        return 'YourGateway';
    }

    public function description(): string
    {
        return 'YourGateway Payment Gateway - Description.';
    }

    public function settings(): array
    {
        return [
            new PaymentGatewaySettingDefinition(
                key: 'api_key',
                label: 'API Key',
                type: 'password',
                required: true,
                placeholder: '...',
                helpText: '...',
            ),
            // Add more settings as needed
        ];
    }
}
```

Supported setting types: `'string'`, `'password'`, `'select'`. For `'select'`, pass an `options` array: `[['value' => '...', 'label' => '...']]`.

### 2. Create the Adapter class

Implement `PaymentGatewayAdapter` in `app/PaymentGateways/Adapters/`:

```php
class YourGatewayAdapter implements PaymentGatewayAdapter
{
    public function __construct(
        private ?GatewayAccount $gatewayAccount = null,
    ) {
        $this->gatewayAccount ??= GatewayAccount::where('name', 'YourGateway')->first();

        if ($this->gatewayAccount === null) {
            throw new RuntimeException(
                'No YourGateway gateway account found. Create a GatewayAccount with name "YourGateway" and configure its settings.',
            );
        }
    }
    // ... implement all interface methods
}
```

### 3. Register in PaymentGatewayManager

Open `app/PaymentGateways/PaymentGatewayManager.php` and register in the constructor:

```php
public function __construct()
{
    $this->register(new AsaasPaymentGatewayDefinition);
    $this->register(new YourGatewayDefinition);
}
```

### 4. Bind in AppServiceProvider

Open `app/Providers/AppServiceProvider.php`:

```php
$this->app->bind(PaymentGatewayAdapter::class, YourGatewayAdapter::class);
$this->app->singleton(PaymentGatewayManager::class);
```

Currently only one adapter is bound. If supporting multiple concurrent gateways, you would need a factory pattern instead.

### 5. Register Access Module

Add a case in `App\AccessControl\AccessModule` enum and configure its actions (gateway resources are view-only for non-account roles).

### 6. Add Webhook Handling

In `app/Http/Controllers/GatewayPostbackController.php`, add a `match` arm:

```php
$postback = match ($gatewayAccount->name) {
    'Asaas' => app(AsaasPaymentGatewayAdapter::class, ['gatewayAccount' => $gatewayAccount])
        ->processPostback($request->all()),
    'YourGateway' => app(YourGatewayAdapter::class, ['gatewayAccount' => $gatewayAccount])
        ->processPostback($request->all()),
    default => abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Gateway provider is not supported.'),
};
```

### 7. Add Read-Only Routes

Register `index` and `show` routes manually in `routes/web.php` under the `auth` middleware group, following the existing pattern for gateway modules.

## Implementing the Adapter Interface

The `PaymentGatewayAdapter` interface requires these 14 methods:

### Customer Management
- `createCustomer(Model $holder): GatewayCustomer` — Creates a customer on the remote gateway. Check for existing customer first to avoid duplicates. Supported holder types: `Client`, `Supplier`, `Trainer`. Store `gateway_reference_key` from the gateway response.
- `findCustomer(GatewayCustomer $customer): ?array` — Returns null on 404.
- `syncCustomer(GatewayCustomer $customer): bool` — PUT update on the gateway, return true/false.

### Payment Management
- `createPayment(Invoice $invoice, GatewayCustomer $customer, array $options): GatewayPayment` — Build payload with billingType, value, dueDate, description, externalReference, discount, fine, interest. Map internal payment methods (boleto, pix, credit_card) to gateway-specific billing types. Store the created payment and update invoice status to `WAITING`.
- `findPayment(GatewayPayment $payment): ?array` — Returns null on 404.
- `payWithCreditCard(GatewayPayment $payment, array $creditCardData): GatewayPayment` — POST to pay endpoint with creditCard + creditCardHolderInfo. Store credit card details if returned.
- `refundPayment(GatewayPayment $payment, ?int $value): GatewayPayment` — Full or partial refund.
- `getPixQrCode(GatewayPayment $payment): ?array` — Returns QR code data (encodedImage, payload).
- `tokenizeCreditCard(array $cardData): ?array` — Tokenize card for future use.

### Transfers
- `createTransfer(array $data): GatewayTransfer` — Requires value, walletId (optional), pixAddressKey (optional), bankAccount (optional), operationType.
- `findTransfer(GatewayTransfer $transfer): ?array` — Returns null on 404.

### Webhooks
- `processPostback(array $payload): GatewayPostback` — Create GatewayPostback with PENDING status, route to handler based on event type, update status to SUCCESS or FAILED.

### Account
- `getBalance(): ?array` — Returns account balance from gateway.

### HTTP Client Pattern

Use Laravel's `Http` facade with proper configuration:

```php
private function client(): PendingRequest
{
    $settings = $this->gatewayAccount->settings ?? [];
    $apiKey = $settings['api_key'] ?? '';
    $baseUrl = $settings['base_url'] ?? 'https://sandbox.asaas.com/api/v3';

    if (blank($apiKey)) {
        throw new RuntimeException('Gateway API key is not configured.');
    }

    return Http::baseUrl($baseUrl)
        ->withHeader('access_token', $apiKey)
        ->acceptJson()
        ->asJson()
        ->timeout(30)
        ->connectTimeout(10)
        ->retry(3, 100, fn ($e, $request) => $e instanceof ConnectionException)
        ->throw();
}
```

Note: the Asaas adapter uses `access_token` header for auth. Other gateways may use Bearer tokens or API keys differently.

### Status Mapping Pattern

Define a private constant mapping gateway status values to the local `TransactionStatus` enum:

```php
private const STATUS_MAP = [
    'GATEWAY_PENDING' => TransactionStatus::PENDING,
    'GATEWAY_CONFIRMED' => TransactionStatus::PAID,
    // ...
];
```

And a mapping method:
```php
private function mapTransactionStatus(string $gatewayStatus): TransactionStatus
{
    return self::STATUS_MAP[$gatewayStatus] ?? TransactionStatus::PENDING;
}
```

Also map billing types similarly:
```php
private const BILLING_TYPE_MAP = [
    'BOLETO' => 'boleto',
    'PIX' => 'pix',
    'CREDIT_CARD' => 'credit_card',
];
```

### Customer Payload Building

The `buildCustomerPayload()` method maps local model fields to gateway-specific fields. Handle each supported holder type (`Client`, `Supplier`, `Trainer`) with a match statement. Clean document and phone numbers with `preg_replace('/\D/', '', ...)`. Handle address fields conditionally using `property_exists()` and `filled()` since not all models have address fields.

For reference, the Asaas adapter maps:
- `address` → `address`
- `address_number` → `addressNumber`
- `address_complement` → `complement`
- `address_district` → `province`
- `address_postal_code` → `postalCode`
- `address_city` → `city`
- `address_state` → `state`

### Payment Storage

After creating a payment on the gateway, store it locally:

```php
private function storeGatewayPayment(array $body, Invoice $invoice, GatewayCustomer $customer): GatewayPayment
{
    $status = $this->mapTransactionStatus($body['status'] ?? 'PENDING');
    $grossValue = (float) ($body['value'] ?? $invoice->total);
    $feeValue = (float) ($body['netValue'] ?? $grossValue);
    $feeValue = $grossValue - $feeValue;

    $payment = GatewayPayment::create([
        'gateway_reference_key' => $body['id'],
        'payment_method' => $this->mapBillingType($body['billingType'] ?? 'UNDEFINED'),
        'payment_date' => isset($body['paymentDate'])
            ? CarbonImmutable::parse($body['paymentDate'])
            : null,
        'status' => $status,
        'gross_value' => $grossValue,
        'fee_value' => max(0, $feeValue),
        'gateway_account_id' => $this->gatewayAccount->id,
        'gateway_customer_id' => $customer->id,
        'invoice_id' => $invoice->id,
    ]);

    if ($invoice->usesGatewayPaymentMethod() && $invoice->status === InvoiceStatus::PENDING) {
        $invoice->update(['status' => InvoiceStatus::WAITING]);
    }

    return $payment;
}
```

## GatewayBillingService

`app/Services/GatewayBillingService.php` is the application service that coordinates billing with the gateway:

- `generate(BillingInvoiceSource&Model $source)` — Generates invoices via `BillingInvoiceService`, then syncs them to the gateway
- `syncInvoice(Invoice $invoice)` — Core sync logic that checks eligibility before contacting the gateway:
  1. Holder must exist
  2. Must be a `RECEIVABLE` operation
  3. Must use a gateway payment method (boleto, pix, credit_card)
  4. Must pass `shouldGenerateGatewayTransaction()` check
  5. Must not already have a `GatewayPayment`
  6. Creates customer on gateway (uses `billingHolder()` from `BillingInvoiceSource`)
  7. Creates payment with description like `"Contrato #5"`, `"Venda #12"`, etc.
  8. Updates invoice status to `WAITING`

The `buildDescription()` method generates descriptions based on source type: `Contract` → "Contrato #N", `DirectLesson` → "Aula avulsa #N", `Sale` → "Venda #N", `Purchase` → "Compra #N".

## Webhook / Postback Handling

### Endpoint

`POST /gateway-postbacks/{gateway_account}/receive` in `GatewayPostbackController::receive()`:

1. Validates the `asaas-access-token` header against the `webhook_token` setting using `hash_equals()` for timing-safe comparison
2. Returns `403` on mismatch (no user authentication required — this is a public endpoint)
3. Routes to the correct adapter based on `$gatewayAccount->name`
4. Returns `201 Created` with `{ id, status }`

### Processing Postbacks

The `processPostback()` method in the adapter:
1. Creates a `GatewayPostback` record with `PENDING` status
2. Routes to the appropriate handler based on event prefix
3. Updates postback to `SUCCESS` on success, or `FAILED` on exception

Event routing (from Asaas adapter):
- `PAYMENT_*` → `handlePaymentPostback()` — Finds `GatewayPayment` by `gateway_reference_key` and updates it from payload
- `TRANSFER*` → `handleTransferPostback()` — Finds `GatewayTransfer` by reference key and updates its status
- `SUBSCRIPTION*` → `subscription` type (no handler yet)
- `CUSTOMER*` → `customer` type (no handler yet)

```php
private function handlePaymentPostback(array $payload, GatewayPostback $postback): void
{
    $paymentData = $payload['payment'] ?? $payload;

    $payment = GatewayPayment::where('gateway_reference_key', $paymentData['id'])->first();

    if ($payment === null) {
        return;
    }

    $this->updateGatewayPaymentFromResponse($payment, $paymentData);
}
```

For new gateways, implement the same pattern: create the postback record, handle the event, update status.

### Registering Webhooks in Asaas

The Asaas adapter exposes helper methods:
- `registerWebhook(string $url, string $type = 'NONE', array $events = [])` — `POST /webhook`
- `unregisterWebhook(string $webhookId)` — `DELETE /webhook/{id}`
- `configureNotifications(array $data)` — `POST /notifications`

Note: the Asaas adapter sends `email` field with the `$type` parameter value — this is an Asaas API peculiarity (the field name is `email` but its value represents the notification type like `NONE`, `ALL`, etc.).

## Sync Command

`php artisan gateway:sync-invoices` finds eligible receivable invoices and syncs them to the gateway:

- Filters: `RECEIVABLE` operation type, `PENDING` status, boleto (any due date) or pix/credit_card (due today), no existing `GatewayPayment`
- Accepts `--invoice=*` option to sync specific invoices
- Reports count of found, synced, and failed invoices

## Sensitive Settings (Password Fields)

`GatewayAccountRequest` handles password field preservation during updates. When a password-type setting is not sent in the update request, it merges the existing value from the database automatically.

`GatewayAccountController::withoutSensitiveSettings()` strips password-type settings from API responses so they are never exposed to the frontend.

## Access Control

Gateway modules are defined in `AccessModule` enum:
- `GATEWAY_ACCOUNT` — Full CRUD (admin only, under Avançado)
- `GATEWAY_PAYMENT`, `GATEWAY_TRANSFER`, `GATEWAY_POSTBACK`, `GATEWAY_CUSTOMER`, `GATEWAY_CREDIT_CARD` — View-only

In `RolePermissionMap`, `ADMINISTRATOR` gets view access to all gateway modules plus full access to `GATEWAY_ACCOUNT`. `MANAGER` and `BILLING` roles are not assigned any gateway permissions.

Run `php artisan access-control:sync` after adding or modifying access modules/permissions.

## Frontend

### Dynamic Settings Form

`gateway_accounts/Details.vue` renders settings dynamically based on the selected provider's `GatewayDefinition`:

```typescript
type GatewaySetting = {
    key: string; label: string; type: string; required: boolean;
    default: unknown; options: { value: string; label: string }[] | null;
    placeholder: string | null; helpText: string | null;
};
type GatewayDefinition = {
    name: string; description: string; settings: GatewaySetting[];
};
```

- Provider selector (`v-select`) shown only when creating
- Settings rendered by type: `select` → `v-select`, `password` → `v-text-field[type=password]`, default → `v-text-field`
- When selecting a provider, defaults are populated: `if (!(setting.key in form.settings)) form.settings[setting.key] = setting.default ?? ''`

### Read-Only Pages

Other gateway pages follow a consistent pattern:
- Use `ReadOnlyDetailsPage.vue` for detail views
- Pass `hide-selection`, `hide-visibility-filter` to `TablePage` for index views
- Permission map: `{ create: false, delete: false, visibility: false }`
- Format data using existing helpers: `formatDate`, `formatDateTime`, `formatCurrency`

## Testing Patterns

When writing tests for gateway features:

### Gateway Postback Tests
```php
public function test_gateway_postback_receive_creates_postback(): void
{
    $gatewayAccount = GatewayAccount::factory()->create([
        'name' => 'Asaas',
        'settings' => [
            'api_key' => 'test-api-key',
            'base_url' => 'https://sandbox.asaas.com/api/v3',
            'webhook_token' => 'valid-token',
        ],
    ]);

    $payload = [
        'event' => 'PAYMENT_RECEIVED',
        'payment' => ['id' => 'pay_123', 'status' => 'RECEIVED', 'value' => 100.00],
    ];

    $response = $this->postJson(
        route('gateway-postbacks.receive', $gatewayAccount),
        $payload,
        ['asaas-access-token' => 'valid-token'],
    );

    $response->assertCreated();
    $this->assertDatabaseHas('gateway_postbacks', [
        'gateway_account_id' => $gatewayAccount->id,
        'postback_event' => 'PAYMENT_RECEIVED',
    ]);
}
```

### Gateway Account Security Tests
- Test that password-type settings are stripped from edit responses
- Test that updates preserve existing password settings when not sent

### Gateway Module Access Tests
- Test that gateway modules are assigned to the correct roles with expected actions
- Run `php artisan access-control:sync` in test setup if needed

### Invoice Gateway Lifecycle Tests
- Test invoice transitions from `PENDING` to `WAITING` when gateway payment is created
- Test that gateway payments prevent invoice udpates (e.g., sale with existing gateway payment returns 403)

### Test Factories
Use existing factories for `GatewayAccount`, `GatewayCustomer`, `GatewayPayment`, etc. Set gateway account name to `'Asaas'` so the adapter auto-resolves it.

## Database Schema

Key tables and their relationships:

- `gateway_accounts` — `name`, `description`, `settings` (JSON), `visibility`
- `gateway_customers` — `gateway_reference_key`, `holder_id`/`holder_type` (polymorphic to Client/Supplier/Trainer), `gateway_account_id`
- `gateway_payments` — `gateway_reference_key`, `payment_method`, `payment_date`, `status`, `gross_value`, `fee_value`, `total` (virtual: `gross_value - fee_value`), `gateway_account_id`, `gateway_customer_id`, `invoice_id`
- `gateway_transfers` — `gateway_reference_key`, `gross_value`, `fee_value`, `total` (virtual), `status`, `gateway_account_id`
- `gateway_postbacks` — `postback_event`, `postback_type`, `payload` (JSON), `status`, `gateway_account_id`
- `gateway_credit_cards` — `gateway_card_token`, `gateway_reference_key`, `status`, `card_brand`, `last_digits`, `gateway_account_id`, `gateway_customer_id`

`gateway_postback_id` on customers/payments/transfers/cards is nullable (made nullable by a later migration to allow creation without postback context).

## Asaas API Specifics (for the current Asaas adapter reference)

When working with the Asaas adapter (`AsaasPaymentGatewayAdapter`), note:

- **Auth**: `access_token` header (not Bearer)
- **Base URLs**: Sandbox `https://sandbox.asaas.com/api/v3`, Production `https://api.asaas.com/api/v3`
- **Key endpoints**: `POST /customers`, `POST /payments`, `POST /payments/{id}/payWithCreditCard`, `POST /payments/{id}/refund`, `GET /payments/{id}/pixQrCode`, `POST /creditCard/tokenize`, `POST /transfers`, `POST /webhook`, `POST /notifications`, `GET /finance/balance`
- **Subscriptions**: `POST /subscriptions`, `DELETE /subscriptions/{id}` (extra methods beyond interface)
- **Discount payload**: `{value, dueDateLimitDays: 0, type: 'FIXED'}` when `discount_value > 0`
- **Transfer status mapping**: `PENDING/BANK_PROCESSING` → `pending`, `DONE` → `paid`, `CANCELED` → `canceled`, `FAILED` → `failed`
- **Webhook auth**: Asaas sends `asaas-access-token` header matching the configured `webhook_token`
- **Postback events**: `PAYMENT_*`, `TRANSFER*`, `SUBSCRIPTION*`, `CUSTOMER*`

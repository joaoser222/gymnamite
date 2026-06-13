<?php

namespace App\Http\Middleware;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\Enums\BillableStatus;
use App\Enums\ClientStatus;
use App\Enums\FinancialAccountType;
use App\Enums\Gateway\PostbackStatus;
use App\Enums\Gateway\TransactionStatus;
use App\Enums\GenderType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LegalType;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Visibility;
use BackedEnum;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
            'enums' => fn () => $this->sharedEnums(),
        ];
    }

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function sharedEnums(): array
    {
        return [
            'accessActions' => $this->enumOptions(AccessAction::class),
            'accessModules' => $this->enumOptions(AccessModule::class),
            'accessRoles' => $this->enumOptions(AccessRole::class),
            'billableStatus' => $this->enumOptions(BillableStatus::class),
            'clientStatus' => $this->enumOptions(ClientStatus::class),
            'financialAccountTypes' => $this->enumOptions(FinancialAccountType::class),
            'genderTypes' => $this->enumOptions(GenderType::class),
            'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
            'invoiceTypes' => $this->enumOptions(InvoiceType::class),
            'legalTypes' => $this->enumOptions(LegalType::class),
            'movementTypes' => $this->enumOptions(MovementType::class),
            'operationTypes' => $this->enumOptions(OperationType::class),
            'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            'postbackStatus' => $this->enumOptions(PostbackStatus::class),
            'productTypes' => $this->enumOptions(ProductType::class),
            'transactionStatus' => $this->enumOptions(TransactionStatus::class),
            'visibility' => $this->enumOptions(Visibility::class),
        ];
    }

    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(string $enumClass): array
    {
        return array_map(
            fn (BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
            ],
            $enumClass::cases(),
        );
    }
}

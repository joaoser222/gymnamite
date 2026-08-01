<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\UserPermissionsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DirectLessonController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\GatewayAccountController;
use App\Http\Controllers\GatewayCreditCardController;
use App\Http\Controllers\GatewayCustomerController;
use App\Http\Controllers\GatewayInvoiceController;
use App\Http\Controllers\GatewayPaymentController;
use App\Http\Controllers\GatewayPostbackController;
use App\Http\Controllers\GatewayTransferController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PlanCategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SelectBoxController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::post('gateway-postbacks/{gateway_account}/receive', [GatewayPostbackController::class, 'receive'])
    ->name('gateway-postbacks.receive');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('auth/permissions', UserPermissionsController::class)->name('auth.permissions');

    Route::inertia('dashboard', 'Home')->name('dashboard');
    Route::get('select-box/{objectName}', SelectBoxController::class)->name('select-box');
    Route::get('contracts/find-client', [ContractController::class, 'findClient'])->name('contracts.find-client');
    Route::get('contracts/find-coupon', [ContractController::class, 'findCoupon'])->name('contracts.find-coupon');
    Route::patch('contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');

    // Pessoas
    Route::module(ClientController::class);
    Route::module(TrainerController::class);
    Route::module(SupplierController::class);

    // Catálogo
    Route::module(ProductController::class);
    Route::module(ModalityController::class);
    Route::module(PlanController::class);
    Route::module(PlanCategoryController::class);

    // Faturamento
    Route::module(ContractController::class);
    Route::module(CouponController::class);
    Route::module(SaleController::class);
    Route::module(PurchaseController::class);
    Route::module(DirectLessonController::class);

    // Financeiro
    Route::module(CostCenterController::class);
    Route::module(FinancialCategoryController::class);
    Route::module(PayableController::class);
    Route::module(ReceivableController::class);
    Route::patch('receivables/{receivable}/mark-paid', [ReceivableController::class, 'markPaid'])->name('receivables.mark-paid');
    Route::post('receivables/{receivable}/request-gateway-invoice', [ReceivableController::class, 'requestGatewayInvoice'])->name('receivables.request-gateway-invoice');
    Route::module(MovementController::class);
    Route::module(TransferController::class);

    // Gateway de Pagamentos
    Route::get('gateway-payments', [GatewayPaymentController::class, 'index'])->name('gateway-payments.index');
    Route::get('gateway-payments/{gateway_payment}', [GatewayPaymentController::class, 'show'])->name('gateway-payments.show');
    Route::get('gateway-transfers', [GatewayTransferController::class, 'index'])->name('gateway-transfers.index');
    Route::get('gateway-transfers/{gateway_transfer}', [GatewayTransferController::class, 'show'])->name('gateway-transfers.show');
    Route::get('gateway-postbacks', [GatewayPostbackController::class, 'index'])->name('gateway-postbacks.index');
    Route::get('gateway-postbacks/{gateway_postback}', [GatewayPostbackController::class, 'show'])->name('gateway-postbacks.show');
    Route::get('gateway-customers', [GatewayCustomerController::class, 'index'])->name('gateway-customers.index');
    Route::get('gateway-customers/{gateway_customer}', [GatewayCustomerController::class, 'show'])->name('gateway-customers.show');
    Route::get('gateway-credit-cards', [GatewayCreditCardController::class, 'index'])->name('gateway-credit-cards.index');
    Route::get('gateway-credit-cards/{gateway_credit_card}', [GatewayCreditCardController::class, 'show'])->name('gateway-credit-cards.show');
    Route::get('gateway-invoices', [GatewayInvoiceController::class, 'index'])->name('gateway-invoices.index');
    Route::get('gateway-invoices/{gateway_invoice}', [GatewayInvoiceController::class, 'show'])->name('gateway-invoices.show');

    // Relatórios
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');

    // Avançado
    Route::module(FinancialAccountController::class);
    Route::module(GatewayAccountController::class);
    Route::get('gateway-accounts/{gateway_account}/invoicing/municipal-options', [GatewayAccountController::class, 'municipalOptions'])->name('gateway-accounts.invoicing.municipal-options');
    Route::get('gateway-accounts/{gateway_account}/invoicing/municipal-services', [GatewayAccountController::class, 'municipalServices'])->name('gateway-accounts.invoicing.municipal-services');
    Route::module(RoleController::class);
    Route::module(UserController::class);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.show');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

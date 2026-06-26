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
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PlanCategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SelectBoxController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

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
    Route::module(MovementController::class);
    Route::module(TransferController::class);

    // Avançado
    Route::module(FinancialAccountController::class);
    Route::module(RoleController::class);
    Route::module(SettingController::class);
});

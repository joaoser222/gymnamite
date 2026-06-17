<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Gera rotas de forma customizada para modulos
        Route::macro('module', function (string $prefix, string $controller) {
            $param = Str::singular($prefix);

            Route::prefix($prefix)->name("{$prefix}.")->group(function () use ($controller, $param) {
                Route::get('/', [$controller, 'index'])->name('index');
                Route::get("/{{{$param}}}", [$controller, 'show'])->name('show');
                Route::post('/', [$controller, 'store'])->name('store');
                Route::put("/{{{$param}}}", [$controller, 'update'])->name('update');
                Route::delete('/', [$controller, 'destroy'])->name('destroy');
                Route::patch('/change-visibility', [$controller, 'changeVisibility'])->name('change-visibility');
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

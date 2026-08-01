<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway_reference_key')->nullable();
            $table->string('status', 30);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('gateway_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['gateway_account_id', 'gateway_reference_key']);
            $table->unique(['invoice_id', 'gateway_payment_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_invoices');
    }
};

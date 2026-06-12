<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_type', 20);
            $table->decimal('balance', 13, 4)->default(0);
            $table->string('holder_name')->nullable();
            $table->string('holder_document')->nullable();
            $table->date('holder_birth_date')->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_agency', 50)->nullable();
            $table->string('bank_account_type', 20)->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->string('visibility', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};

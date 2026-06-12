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
        Schema::create('gateway_credit_cards', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_card_token');
            $table->string('gateway_reference_key');
            $table->string('status', 20);
            $table->string('card_brand', 20);
            $table->string('last_digits', 4);
            $table->foreignId('gateway_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_postback_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_credit_cards');
    }
};

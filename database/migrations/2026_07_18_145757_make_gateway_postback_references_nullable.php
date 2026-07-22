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
        Schema::table('gateway_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('gateway_postback_id')->nullable()->change();
        });

        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->change();
            $table->unsignedBigInteger('gateway_postback_id')->nullable()->change();
        });

        Schema::table('gateway_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('gateway_postback_id')->nullable()->change();
        });

        Schema::table('gateway_credit_cards', function (Blueprint $table) {
            $table->string('gateway_card_token')->nullable()->change();
            $table->string('gateway_reference_key')->nullable()->change();
            $table->string('status', 20)->nullable()->change();
            $table->string('card_brand', 20)->nullable()->change();
            $table->unsignedBigInteger('gateway_postback_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateway_credit_cards', function (Blueprint $table) {
            $table->string('gateway_card_token')->nullable(false)->change();
            $table->string('gateway_reference_key')->nullable(false)->change();
            $table->string('status', 20)->nullable(false)->change();
            $table->string('card_brand', 20)->nullable(false)->change();
            $table->unsignedBigInteger('gateway_postback_id')->nullable(false)->change();
        });

        Schema::table('gateway_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('gateway_postback_id')->nullable(false)->change();
        });

        Schema::table('gateway_payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable(false)->change();
            $table->unsignedBigInteger('gateway_postback_id')->nullable(false)->change();
        });

        Schema::table('gateway_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('gateway_postback_id')->nullable(false)->change();
        });
    }
};

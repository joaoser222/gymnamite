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
        Schema::table('sales', function (Blueprint $table) {
            $table->date('first_due_date')->nullable()->after('payment_method');
            $table->unsignedInteger('installments')->default(1)->after('first_due_date');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->date('first_due_date')->nullable()->after('payment_method');
            $table->unsignedInteger('installments')->default(1)->after('first_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['first_due_date', 'installments']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['first_due_date', 'installments']);
        });
    }
};

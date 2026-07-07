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
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('gross_value', 13, 4)->nullable()->after('price');
            $table->decimal('discount_value', 13, 4)->default(0)->after('gross_value');
            $table->decimal('total', 13, 4)->nullable()->after('discount_value');
            $table->string('payment_method', 20)->nullable()->after('status');
            $table->date('first_due_date')->nullable()->after('start_date');
            $table->unsignedInteger('installments')->nullable()->after('first_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'gross_value',
                'discount_value',
                'total',
                'payment_method',
                'first_due_date',
                'installments',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the unique constraints required by the reference-data seeders that
     * use upsert() with these conflict columns. Without them, PostgreSQL
     * rejects the generated ON CONFLICT clause (SQLSTATE 42P10).
     */
    public function up(): void
    {
        Schema::table('ufs', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('product_unities', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('financial_categories', function (Blueprint $table) {
            $table->unique(['name', 'cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ufs', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('product_unities', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('financial_categories', function (Blueprint $table) {
            $table->dropUnique(['name', 'cost_center_id']);
        });
    }
};

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
        Schema::table('roles', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::table('permission_user', function (Blueprint $table) {
            $table->unique(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropUnique(['role_id', 'permission_id']);
        });

        Schema::table('permission_user', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'permission_id']);
        });
    }
};

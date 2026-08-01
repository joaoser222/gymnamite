<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_accounts', function (Blueprint $table): void {
            $table->boolean('invoicing_enabled')->default(false)->after('description');
        });

        Schema::table('gateway_postbacks', function (Blueprint $table): void {
            $table->string('external_event_key')->nullable()->after('postback_type');
            $table->unique(['gateway_account_id', 'external_event_key']);
        });
    }

    public function down(): void
    {
        Schema::table('gateway_postbacks', function (Blueprint $table): void {
            $table->dropUnique(['gateway_account_id', 'external_event_key']);
            $table->dropColumn('external_event_key');
        });

        Schema::table('gateway_accounts', function (Blueprint $table): void {
            $table->dropColumn('invoicing_enabled');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_accounts', function (Blueprint $table): void {
            $table->boolean('invoicing_supported')->default(false)->after('invoicing_enabled');
            $table->boolean('invoicing_configured')->default(false)->after('invoicing_supported');
        });

        DB::table('gateway_accounts')->where('name', 'Asaas')->update(['invoicing_supported' => true]);

        Schema::table('gateway_invoices', function (Blueprint $table): void {
            $table->string('status_description')->nullable()->after('status');
            $table->string('invoice_number')->nullable()->after('status_description');
            $table->string('validation_code')->nullable()->after('invoice_number');
            $table->text('service_description')->nullable()->after('validation_code');
            $table->text('observations')->nullable()->after('service_description');
            $table->decimal('value', 13, 4)->nullable()->after('observations');
            $table->decimal('deductions', 13, 4)->nullable()->after('value');
            $table->date('effective_date')->nullable()->after('deductions');
            $table->text('pdf_url')->nullable()->after('effective_date');
            $table->text('xml_url')->nullable()->after('pdf_url');
            $table->string('municipal_service_id')->nullable()->after('xml_url');
            $table->string('municipal_service_code')->nullable()->after('municipal_service_id');
            $table->string('municipal_service_description')->nullable()->after('municipal_service_code');
            $table->string('external_reference')->nullable()->after('municipal_service_description');
        });
    }

    public function down(): void
    {
        Schema::table('gateway_invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'status_description', 'invoice_number', 'validation_code', 'service_description',
                'observations', 'value', 'deductions', 'effective_date', 'pdf_url', 'xml_url',
                'municipal_service_id', 'municipal_service_code', 'municipal_service_description',
                'external_reference',
            ]);
        });

        Schema::table('gateway_accounts', function (Blueprint $table): void {
            $table->dropColumn('invoicing_supported');
            $table->dropColumn('invoicing_configured');
        });
    }
};

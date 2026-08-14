<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_transfer_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_account_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('holder_name', 255);
            $table->string('holder_document', 20);
            $table->text('pix_key');
            $table->string('pix_key_type', 20);
            $table->string('visibility', 10)->default('visible');
            $table->timestamps();

            $table->unique(['gateway_account_id', 'label']);
        });

        Schema::table('gateway_transfers', function (Blueprint $table) {
            $table->foreignId('gateway_transfer_recipient_id')
                ->nullable()
                ->after('gateway_account_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gateway_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gateway_transfer_recipient_id');
        });

        Schema::dropIfExists('gateway_transfer_recipients');
    }
};

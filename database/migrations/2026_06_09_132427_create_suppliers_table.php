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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('document', 14);
            $table->date('birth_date');
            $table->string('phone', 11);
            $table->string('address', 200)->nullable();
            $table->string('address_number', 10)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_district', 100)->nullable();
            $table->string('address_postal_code', 8)->nullable();
            $table->string('status', 10);
            $table->string('visibility', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

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
        Schema::create('corporate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Company Information
            $table->string('company_legal_name', 255);
            $table->string('trade_license_number', 100)->nullable();
            $table->string('vat_registration_number', 50)->nullable();
            $table->date('incorporation_date')->nullable();
            
            // Contact Person
            $table->string('primary_contact_name', 255);
            $table->string('primary_contact_email', 255);
            $table->string('primary_contact_phone', 20);
            
            // Business Details
            $table->string('industry', 100)->nullable();
            $table->integer('employee_count')->nullable();
            $table->bigInteger('annual_revenue')->nullable();
            
            // Credit & Payment Terms
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->enum('payment_terms', ['net_15', 'net_30', 'net_45', 'net_60', 'prepaid'])->default('prepaid');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_profiles');
    }
};

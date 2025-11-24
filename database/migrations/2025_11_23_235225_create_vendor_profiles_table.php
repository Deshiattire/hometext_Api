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
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Business Information
            $table->string('store_name', 255);
            $table->string('store_slug', 255)->unique();
            $table->string('store_logo', 255)->nullable();
            $table->string('store_banner', 255)->nullable();
            $table->text('store_description')->nullable();
            
            // Legal & Verification
            $table->string('business_license', 100)->nullable();
            $table->string('tax_certificate', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Performance Metrics
            $table->decimal('rating', 3, 2)->default(0.00)->comment('Average rating 0-5');
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->unsignedInteger('total_products')->default(0);
            
            // Bank Details (encrypted)
            $table->string('bank_name', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('account_holder_name', 255)->nullable();
            $table->string('routing_number', 50)->nullable();
            
            // Commission
            $table->decimal('commission_rate', 5, 2)->default(10.00)->comment('Platform commission %');
            
            $table->timestamps();
            
            $table->index('store_slug');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};

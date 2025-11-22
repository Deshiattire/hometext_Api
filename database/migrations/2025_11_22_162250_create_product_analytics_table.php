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
        Schema::create('product_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('views_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->integer('purchase_count')->default(0);
            $table->integer('wishlist_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0); // percentage
            $table->timestamps();
            
            $table->unique('product_id'); // One analytics record per product
            $table->index('views_count');
            $table->index('purchase_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_analytics');
    }
};

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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable(); // Can be null for guest reviews
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();
            $table->integer('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('review')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_recommended')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_helpful_count')->default(0);
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('user_id');
            $table->index('rating');
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates a table for storing multiple images per category
     * Supports different image types (thumbnail, banner, gallery, etc.)
     */
    public function up(): void
    {
        Schema::create('category_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            
            // Image information
            $table->string('image_path');
            $table->string('image_type')->default('primary')->comment('primary, thumbnail, banner, gallery');
            $table->string('alt_text')->nullable();
            
            // Image dimensions (for optimization)
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('file_size')->nullable()->comment('Size in bytes');
            
            // Display order
            $table->integer('position')->default(0);
            $table->boolean('is_primary')->default(false);
            
            // Storage information
            $table->string('storage_disk')->default('public')->comment('local, public, s3, etc.');
            $table->string('mime_type')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('category_id');
            $table->index('image_type');
            $table->index('is_primary');
            $table->index('position');
            $table->index(['category_id', 'image_type', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_images');
    }
};



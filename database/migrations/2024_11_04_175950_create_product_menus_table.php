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
        Schema::create('product_menus', function (Blueprint $table) {
            $table->id();
            $table->string('menu_type')->nullable()->comment('Horizontal, Vertical');
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('child_id')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_menus');
    }
};

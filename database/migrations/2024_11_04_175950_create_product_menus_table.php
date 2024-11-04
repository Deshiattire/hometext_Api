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
            $table->enum('menu_type', ['Horizontal', 'Vertical'])->default('Horizontal');
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->bigInteger('parent_id')->default(0);
            $table->bigInteger('child_id')->default(0);
            $table->string('link')->nullable();
            $table->bigInteger('sl')->default(0)->comment('menu serial');
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

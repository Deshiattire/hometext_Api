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
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_id')->after('to_shop_id');

            $table->foreign('attribute_id')->references('id')->on('product_attributes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_products', function (Blueprint $table) {
            //
        });
    }
};

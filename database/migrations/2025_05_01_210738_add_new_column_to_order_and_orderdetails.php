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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipment_track_no', 50)->nullable()->after('order_number');
            $table->tinyInteger('is_gift')->default(0)->after('order_number');
            $table->string('coupon', 20)->nullable()->after('order_number');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('attributes_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('attribute_value_id')->nullable()->after('order_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->test('address')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });

        Schema::table('order_details', function (Blueprint $table) {
            //
        });

        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds fields to support guest checkout functionality.
     * Guest orders are identified by is_guest_order = true and have
     * guest information stored directly in the orders table.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Guest order identifier
            $table->boolean('is_guest_order')->default(false)->after('id');
            
            // Guest customer information (used when customer_id is null or for guest orders)
            $table->string('guest_email')->nullable()->after('is_guest_order');
            $table->string('guest_phone', 20)->nullable()->after('guest_email');
            $table->string('guest_name')->nullable()->after('guest_phone');
            
            // Guest token for order tracking without login
            $table->string('guest_token', 64)->nullable()->unique()->after('guest_name');
            
            // Shipping address fields for guest orders
            $table->string('shipping_name')->nullable()->after('guest_token');
            $table->string('shipping_phone', 20)->nullable()->after('shipping_name');
            $table->string('shipping_email')->nullable()->after('shipping_phone');
            $table->text('shipping_address_line_1')->nullable()->after('shipping_email');
            $table->string('shipping_address_line_2')->nullable()->after('shipping_address_line_1');
            $table->string('shipping_city')->nullable()->after('shipping_address_line_2');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_state');
            $table->string('shipping_country', 100)->nullable()->after('shipping_postal_code');
            
            // Billing address fields for guest orders
            $table->string('billing_name')->nullable()->after('shipping_country');
            $table->string('billing_phone', 20)->nullable()->after('billing_name');
            $table->string('billing_email')->nullable()->after('billing_phone');
            $table->text('billing_address_line_1')->nullable()->after('billing_email');
            $table->string('billing_address_line_2')->nullable()->after('billing_address_line_1');
            $table->string('billing_city')->nullable()->after('billing_address_line_2');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code', 20)->nullable()->after('billing_state');
            $table->string('billing_country', 100)->nullable()->after('billing_postal_code');
            
            // Additional fields
            $table->text('order_notes')->nullable()->after('billing_country');
            $table->string('ip_address', 45)->nullable()->after('order_notes');
            $table->string('user_agent')->nullable()->after('ip_address');
            
            // Make customer_id nullable for guest orders
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // Add index for guest order lookups
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['is_guest_order', 'guest_email'], 'idx_guest_orders');
            $table->index('guest_token', 'idx_guest_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_guest_orders');
            $table->dropIndex('idx_guest_token');
            
            $table->dropColumn([
                'is_guest_order',
                'guest_email',
                'guest_phone',
                'guest_name',
                'guest_token',
                'shipping_name',
                'shipping_phone',
                'shipping_email',
                'shipping_address_line_1',
                'shipping_address_line_2',
                'shipping_city',
                'shipping_state',
                'shipping_postal_code',
                'shipping_country',
                'billing_name',
                'billing_phone',
                'billing_email',
                'billing_address_line_1',
                'billing_address_line_2',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'order_notes',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};

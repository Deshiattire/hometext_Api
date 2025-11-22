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
        Schema::table('products', function (Blueprint $table) {
            // Basic product information
            $table->text('short_description')->nullable()->after('description');
            $table->enum('visibility', ['visible', 'catalog', 'search', 'hidden'])->default('visible')->after('status');
            $table->enum('type', ['simple', 'variable', 'grouped', 'bundle'])->default('simple')->after('visibility');
            $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            
            // Pricing & Offers
            $table->decimal('tax_rate', 5, 2)->nullable()->default(0)->after('discount_end');
            $table->boolean('tax_included')->default(false)->after('tax_rate');
            $table->string('tax_class', 50)->nullable()->default('standard')->after('tax_included');
            $table->string('currency', 3)->default('BDT')->after('tax_class');
            $table->string('currency_symbol', 10)->default('৳')->after('currency');
            
            // Inventory & Stock
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder', 'preorder'])->default('in_stock')->after('stock');
            $table->integer('low_stock_threshold')->nullable()->default(10)->after('stock_status');
            $table->boolean('allow_backorders')->default(false)->after('low_stock_threshold');
            $table->boolean('manage_stock')->default(true)->after('allow_backorders');
            $table->integer('sold_count')->default(0)->after('manage_stock');
            $table->timestamp('restock_date')->nullable()->after('sold_count');
            
            // Shipping & Delivery
            $table->decimal('weight', 8, 2)->nullable()->after('restock_date');
            $table->string('weight_unit', 10)->default('kg')->after('weight');
            $table->decimal('length', 8, 2)->nullable()->after('weight_unit');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->string('dimension_unit', 10)->default('cm')->after('height');
            $table->string('shipping_class', 50)->nullable()->default('standard')->after('dimension_unit');
            $table->boolean('free_shipping')->default(false)->after('shipping_class');
            $table->string('ships_from_country', 100)->nullable()->after('free_shipping');
            $table->string('ships_from_city', 100)->nullable()->after('ships_from_country');
            $table->integer('min_delivery_days')->nullable()->default(3)->after('ships_from_city');
            $table->integer('max_delivery_days')->nullable()->default(7)->after('min_delivery_days');
            $table->boolean('express_available')->default(false)->after('max_delivery_days');
            
            // Product Flags & Badges
            $table->boolean('is_bestseller')->default(false)->after('isTrending');
            $table->boolean('is_limited_edition')->default(false)->after('is_bestseller');
            $table->boolean('is_exclusive')->default(false)->after('is_limited_edition');
            $table->boolean('is_eco_friendly')->default(false)->after('is_exclusive');
            
            // Additional Information
            $table->integer('minimum_order_quantity')->default(1)->after('is_eco_friendly');
            $table->integer('maximum_order_quantity')->nullable()->after('minimum_order_quantity');
            $table->boolean('has_warranty')->default(false)->after('maximum_order_quantity');
            $table->integer('warranty_duration')->nullable()->after('has_warranty');
            $table->string('warranty_duration_unit', 20)->nullable()->default('months')->after('warranty_duration');
            $table->enum('warranty_type', ['manufacturer', 'seller', 'extended'])->nullable()->after('warranty_duration_unit');
            $table->text('warranty_details')->nullable()->after('warranty_type');
            $table->boolean('returnable')->default(true)->after('warranty_details');
            $table->integer('return_window_days')->nullable()->default(7)->after('returnable');
            $table->text('return_conditions')->nullable()->after('return_window_days');
            
            // Timestamps
            $table->timestamp('published_at')->nullable()->after('updated_at');
            
            // Foreign key for parent_id (self-referencing)
            $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('parent_id');
            $table->index('visibility');
            $table->index('type');
            $table->index('stock_status');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['type']);
            $table->dropIndex(['stock_status']);
            $table->dropIndex(['published_at']);
            
            $table->dropColumn([
                'short_description', 'visibility', 'type', 'parent_id',
                'tax_rate', 'tax_included', 'tax_class', 'currency', 'currency_symbol',
                'stock_status', 'low_stock_threshold', 'allow_backorders', 'manage_stock',
                'sold_count', 'restock_date',
                'weight', 'weight_unit', 'length', 'width', 'height', 'dimension_unit',
                'shipping_class', 'free_shipping', 'ships_from_country', 'ships_from_city',
                'min_delivery_days', 'max_delivery_days', 'express_available',
                'is_bestseller', 'is_limited_edition', 'is_exclusive', 'is_eco_friendly',
                'minimum_order_quantity', 'maximum_order_quantity',
                'has_warranty', 'warranty_duration', 'warranty_duration_unit', 'warranty_type', 'warranty_details',
                'returnable', 'return_window_days', 'return_conditions',
                'published_at'
            ]);
        });
    }
};

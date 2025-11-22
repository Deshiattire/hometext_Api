<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // First, remove any duplicate slugs (keep the first one, update others)
            // This is a safety measure in case there are existing duplicates
            $duplicates = DB::table('products')
                ->select('slug', DB::raw('COUNT(*) as count'))
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->groupBy('slug')
                ->having('count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                $products = DB::table('products')
                    ->where('slug', $duplicate->slug)
                    ->orderBy('id')
                    ->get();

                // Keep first product's slug, update others
                foreach ($products->skip(1) as $index => $product) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['slug' => $product->slug . '-' . ($index + 1)]);
                }
            }

            // Add unique index on slug
            $table->unique('slug', 'products_slug_unique');
            $table->index('slug', 'products_slug_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
            $table->dropIndex('products_slug_index');
        });
    }
};

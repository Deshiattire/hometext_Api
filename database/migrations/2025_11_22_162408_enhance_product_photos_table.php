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
        Schema::table('product_photos', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('photo');
            $table->integer('width')->nullable()->after('alt_text');
            $table->integer('height')->nullable()->after('width');
            $table->integer('position')->default(0)->after('height');
            
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropIndex(['position']);
            $table->dropColumn(['alt_text', 'width', 'height', 'position']);
        });
    }
};

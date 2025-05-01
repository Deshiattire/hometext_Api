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
        Schema::create('order_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->tinyInteger('wrapping')->default(1)->comment('1=wrapping, 2=not wrapping');
            $table->string('sender_name', 150);
            $table->string('recipient_name', 150);
            $table->string('message', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_gifts');
    }
};

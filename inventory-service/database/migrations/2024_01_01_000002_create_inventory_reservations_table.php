<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->unsignedBigInteger('order_id');
            $table->string('product_name');
            $table->integer('quantity');
            $table->integer('reserved_quantity');
            $table->string('state')->default('requested');
            $table->text('state_failure_description')->nullable();
            $table->timestamps();

            $table->index('state');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};

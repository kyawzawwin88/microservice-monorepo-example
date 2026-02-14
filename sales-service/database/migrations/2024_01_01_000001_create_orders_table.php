<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->json('items');
            $table->string('state')->default('requested');
            $table->text('state_failure_description')->nullable();
            $table->timestamps();

            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->unique()->index();
            $table->unsignedBigInteger('invoice_id');
            $table->string('invoice_number')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->string('transaction_reference')->unique();
            $table->string('state')->default('requested');
            $table->text('state_failure_description')->nullable();
            $table->timestamps();

            $table->index('state');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

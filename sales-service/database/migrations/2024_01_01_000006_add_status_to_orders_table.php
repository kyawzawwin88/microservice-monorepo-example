<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a 'status' column to orders table for business logic tracking.
     *
     * 'state' = microservice event-processing lifecycle (requested/completed/failed)
     * 'status' = business order status (submitted/paid/delivered)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('submitted')->after('state');
            $table->index('status');
        });

        // Migrate existing records: if 'state' has a business value, move it to 'status'
        // and reset 'state' to 'completed' (since those records were already processed).
        $businessStates = ['submitted', 'paid', 'delivered'];
        DB::table('orders')
            ->whereIn('state', $businessStates)
            ->get()
            ->each(function ($order) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'status' => $order->state,
                        'state' => 'completed',
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};

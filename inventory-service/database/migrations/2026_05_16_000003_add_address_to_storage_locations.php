<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storage_locations')) {
            return;
        }

        Schema::table('storage_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('storage_locations', 'address_street')) {
                $table->text('address_street')->nullable()->after('code');
            }
            if (! Schema::hasColumn('storage_locations', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('address_street');
            }
            if (! Schema::hasColumn('storage_locations', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('country_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('storage_locations')) {
            return;
        }

        Schema::table('storage_locations', function (Blueprint $table) {
            $columns = ['postal_code', 'country_code', 'address_street'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('storage_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

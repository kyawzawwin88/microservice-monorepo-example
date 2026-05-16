<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_items', 'has_variations')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->boolean('has_variations')->default(false)->after('sku');
            });
        }

        if (! Schema::hasTable('storage_locations')) {
            Schema::create('storage_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('variation_dimensions')) {
            Schema::create('variation_dimensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('variation_dimension_values')) {
            Schema::create('variation_dimension_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('variation_dimension_id')->constrained('variation_dimensions')->cascadeOnDelete();
                $table->string('value');
                $table->timestamps();
                $table->unique(['variation_dimension_id', 'value']);
            });
        }

        if (! Schema::hasTable('inventory_variations')) {
            Schema::create('inventory_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('sku')->unique();
                $table->string('attribute_hash');
                $table->string('label');
                $table->timestamps();
                $table->unique(['inventory_item_id', 'attribute_hash']);
            });
        }

        if (! Schema::hasTable('inventory_variation_dimension_value')) {
            Schema::create('inventory_variation_dimension_value', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inventory_variation_id');
                $table->unsignedBigInteger('variation_dimension_value_id');
                $table->foreign('inventory_variation_id', 'ivdv_variation_fk')
                    ->references('id')->on('inventory_variations')->cascadeOnDelete();
                $table->foreign('variation_dimension_value_id', 'ivdv_dimension_value_fk')
                    ->references('id')->on('variation_dimension_values')->cascadeOnDelete();
                $table->unique(['inventory_variation_id', 'variation_dimension_value_id'], 'variation_dimension_value_unique');
            });
        }

        if (! Schema::hasTable('stock_balances')) {
            Schema::create('stock_balances', function (Blueprint $table) {
                $table->id();
                $table->string('balanceable_type');
                $table->unsignedBigInteger('balanceable_id');
                $table->foreignId('storage_location_id')->constrained('storage_locations')->cascadeOnDelete();
                $table->integer('quantity_available')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->timestamps();
                $table->unique(['balanceable_type', 'balanceable_id', 'storage_location_id'], 'stock_balance_unique');
                $table->index(['storage_location_id', 'balanceable_type', 'balanceable_id'], 'stock_bal_loc_balanceable_idx');
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->string('movement_type');
                $table->string('balanceable_type');
                $table->unsignedBigInteger('balanceable_id');
                $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
                $table->foreignId('source_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
                $table->foreignId('destination_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
                $table->integer('quantity');
                $table->string('reference')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
                $table->index(['balanceable_type', 'balanceable_id', 'created_at'], 'stock_mov_balanceable_created_idx');
            });
        }

        Schema::table('inventory_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_reservations', 'inventory_variation_id')) {
                $table->foreignId('inventory_variation_id')->nullable()->after('order_id')->constrained('inventory_variations')->nullOnDelete();
            }
            if (! Schema::hasColumn('inventory_reservations', 'storage_location_id')) {
                $table->foreignId('storage_location_id')->nullable()->after('inventory_variation_id')->constrained('storage_locations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_reservations', 'storage_location_id')) {
            Schema::table('inventory_reservations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('storage_location_id');
            });
        }

        if (Schema::hasColumn('inventory_reservations', 'inventory_variation_id')) {
            Schema::table('inventory_reservations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('inventory_variation_id');
            });
        }

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('inventory_variation_dimension_value');
        Schema::dropIfExists('inventory_variations');
        Schema::dropIfExists('variation_dimension_values');
        Schema::dropIfExists('variation_dimensions');
        Schema::dropIfExists('storage_locations');

        if (Schema::hasColumn('inventory_items', 'has_variations')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('has_variations');
            });
        }
    }
};

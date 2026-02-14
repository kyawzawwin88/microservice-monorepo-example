<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the jobs table in the shared event bus database.
 * This table is used for cross-service communication via database queue.
 */
return new class extends Migration
{
    protected $connection = 'eventbus';

    public function up(): void
    {
        Schema::connection('eventbus')->create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('eventbus')->dropIfExists('jobs');
    }
};

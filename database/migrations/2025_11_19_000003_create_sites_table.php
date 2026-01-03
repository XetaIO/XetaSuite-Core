<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->boolean('is_headquarters')->default(false);
            $table->integer('zone_count')->default(0);
            $table->string('email', 100)->nullable();
            $table->string('office_phone', 20)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();

            $table->timestamps();
        });

        // Create a partial unique index on is_headquarters where is_headquarters = true
        // This ensures only one headquarters site exists
        DB::statement('CREATE UNIQUE INDEX unique_headquarters ON sites (is_headquarters) WHERE is_headquarters = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};

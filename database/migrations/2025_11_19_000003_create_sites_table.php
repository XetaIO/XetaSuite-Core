<?php

declare(strict_types=1);

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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->boolean('is_headquarters')->default(false);
            $table->integer('zone_count')->default(0);
            $table->string('email', 100)->nullable();
            $table->string('office_phone', 20)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();

            $table->timestamps();

            $table->unique(['is_headquarters'])->where('is_headquarters', true); // Ensure only one headquarters site exists
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};

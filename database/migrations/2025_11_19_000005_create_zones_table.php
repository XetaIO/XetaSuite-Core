<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Site;
use XetaSuite\Models\Zone;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->restrictOnDelete();

            $table->foreignIdFor(Zone::class, 'parent_id')
                ->nullable()
                ->constrained('zones')
                ->restrictOnDelete();

            $table->string('name');
            $table->boolean('allow_material')->default(true);
            $table->unsignedInteger('material_count')->default(0);

            $table->timestamps();

            $table->unique(['site_id', 'name'], 'zones_site_name_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};

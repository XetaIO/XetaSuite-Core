<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use XetaSuite\Models\Material;
use XetaSuite\Models\Item;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_material', function (Blueprint $table): void {
            $table->foreignIdFor(Item::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Material::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['material_id', 'item_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_material');
    }
};

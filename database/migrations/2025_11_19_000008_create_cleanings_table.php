<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cleanings', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->restrictOnDelete();

            $table->foreignIdFor(Material::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('material_name', 100)
                ->nullable()
                ->comment('The name of the material if the material is deleted.');

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable()
                ->comment('The name of the user who created the cleaning if the user is deleted.');

            $table->foreignIdFor(User::class, 'edited_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->mediumText('description')->nullable();
            $table->string('type', 50)->default('daily');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleanings');
    }
};

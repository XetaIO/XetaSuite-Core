<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\User;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable()
                ->comment('The name of the user who created the company if the user is deleted.');

            $table->string('name')->unique();
            $table->text('description')->nullable();

            // Company types (item_provider, maintenance_provider)
            $table->json('types')->default('[]')
                ->comment('Array of company types: item_provider, maintenance_provider');

            // Contact information
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();

            // Counts
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('maintenance_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

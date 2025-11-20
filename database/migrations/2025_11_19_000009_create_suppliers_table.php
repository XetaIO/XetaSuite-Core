<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable()
                ->comment('The name of the user who created the supplier if the user is deleted.');

            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('item_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

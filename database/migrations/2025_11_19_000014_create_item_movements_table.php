<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Item;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Item::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['entry', 'exit']);
            $table->integer('quantity'); // Positive or negative regarding the type
            $table->decimal('unit_price', 10, 2); // Unit price AT THE MOVEMENT moment
            $table->decimal('total_price', 10, 2); // Total price = quantity * unit_price

            // Entries
            $table->foreignIdFor(Supplier::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('supplier_name', 150)->nullable();
            $table->string('supplier_invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();

            // For linking to maintenance
            $table->nullableMorphs('movable'); // movable_type, movable_id

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('created_by_name', 100)->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('movement_date'); // Real date of movement

            $table->timestamps();

            // Index pour les rapports
            $table->index(['item_id', 'type', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_movements');
    }
};

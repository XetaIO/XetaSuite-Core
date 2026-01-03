<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Company;
use XetaSuite\Models\Item;
use XetaSuite\Models\User;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('item_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Item::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Company::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('company_name', 150)->nullable();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('created_by_name', 100)->nullable();

            $table->decimal('price', 10, 2);
            $table->timestamp('effective_date'); // Date price application
            $table->text('notes')->nullable(); // Reason of price change

            $table->timestamps();

            $table->index(['item_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};

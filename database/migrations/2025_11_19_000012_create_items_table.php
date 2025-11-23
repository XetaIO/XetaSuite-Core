<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Site;
use XetaSuite\Models\Supplier;
use XetaSuite\Models\User;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->restrictOnDelete();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable()
                ->comment('The name of the user who created the item if the user is deleted.');

            $table->foreignIdFor(Supplier::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('supplier_name', 150)
                ->nullable()
                ->comment('The name of the supplier who supplied the item if the supplier is deleted.');
            $table->string('supplier_reference', 100)
                ->nullable()
                ->comment('The supplier reference for this item.');

            $table->foreignIdFor(User::class, 'edited_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('reference')->nullable()->index();
            $table->decimal('purchase_price', 10, 2)->default(0.00)
                ->comment('Current purchase price from supplier');
            $table->string('currency', 3)->default('EUR');

            $table->unsignedInteger('item_entry_total')->default(0);
            $table->unsignedInteger('item_exit_total')->default(0);
            $table->unsignedInteger('item_entry_count')->default(0);
            $table->unsignedInteger('item_exit_count')->default(0);

            $table->unsignedInteger('material_count')->default(0);
            $table->unsignedInteger('qrcode_flash_count')->default(0);

            $table->boolean('number_warning_enabled')->default(false);
            $table->unsignedInteger('number_warning_minimum')->default(0);
            $table->boolean('number_critical_enabled')->default(false);
            $table->unsignedInteger('number_critical_minimum')->default(0);

            $table->timestamps();

            $table->unique(['name', 'site_id'], 'items_name_site_primary');
            $table->unique(['reference', 'site_id'], 'items_reference_site_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

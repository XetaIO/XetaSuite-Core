<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use XetaSuite\Models\Company;
use XetaSuite\Models\Maintenance;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_maintenance', function (Blueprint $table) {
            $table->foreignIdFor(Company::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Maintenance::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['maintenance_id', 'company_id']);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_maintenance');
    }
};

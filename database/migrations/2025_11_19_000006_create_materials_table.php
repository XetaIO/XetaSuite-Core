<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\User;
use XetaSuite\Models\Zone;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable()
                ->comment('The name of the user who created the material if the user is deleted.');

            $table->foreignIdFor(Zone::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('zone_name', 100)
                ->nullable()
                ->comment('The name of the zone if the zone is deleted.');

            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('qrcode_flash_count')->default(0);
            $table->unsignedInteger('incident_count')->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('maintenance_count')->default(0);
            $table->unsignedInteger('cleaning_count')->default(0);

            $table->boolean('cleaning_alert')->default(false);
            $table->boolean('cleaning_alert_email')->default(false);
            $table->tinyInteger('cleaning_alert_frequency_repeatedly')->default(1);
            $table->timestamp('last_cleaning_at')->nullable();
            $table->timestamp('last_cleaning_alert_send_at')->nullable();
            $table->string('cleaning_alert_frequency_type', 50)->default('daily');

            $table->timestamps();

            $table->unique(['zone_id', 'name'], 'materials_zone_name_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};

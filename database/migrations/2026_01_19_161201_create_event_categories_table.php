<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Site;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('color', 7)->default('#465fff'); // Hex color
            $table->text('description')->nullable();
            $table->unsignedInteger('calendar_event_count')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};

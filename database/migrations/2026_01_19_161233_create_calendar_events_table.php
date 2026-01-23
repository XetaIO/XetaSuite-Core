<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\EventCategory;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(EventCategory::class, 'event_category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'created_by_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('created_by_name', 100)
                ->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable(); // Used when no category is set
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->timestamps();

            $table->index(['site_id', 'start_at']);
            $table->index(['site_id', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};

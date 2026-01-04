<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_user', function (Blueprint $table): void {
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Site::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('manager')->default(false);
            $table->timestamps();

            $table->primary(['user_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_user');
    }
};

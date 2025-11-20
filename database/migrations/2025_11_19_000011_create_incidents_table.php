<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XetaSuite\Models\Material;
use XetaSuite\Models\Site;
use XetaSuite\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Site::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('site_name', 150)
                ->nullable()
                ->comment('The name of the site if the site is deleted.');

            $table->foreignIdFor(Material::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'reported_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('reported_by_name', 100)
                ->nullable()
                ->comment('The name of the user who reported the incident if the user is deleted.');

            $table->foreignIdFor(User::class, 'edited_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('description');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('status', 50)->default('open')->index();
            $table->string('severity', 20)->default('low')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

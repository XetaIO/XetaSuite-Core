<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// use XetaSuite\Models\User; // inutile ici, on référence la table directement

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('username', 100)->unique()->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email', 255)->unique()->index();
            $table->string('password')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('cell_phone')->nullable();
            $table->rememberToken();

            $table->unsignedBigInteger('current_site_id')->nullable();

            $table->unsignedInteger('incident_count')->default(0);
            $table->unsignedInteger('maintenance_count')->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('item_exit_count')->default(0);
            $table->unsignedInteger('item_entry_count')->default(0);
            $table->unsignedInteger('cleaning_count')->default(0);

            $table->timestamp('end_employment_contract')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->dateTime('last_login_date')->nullable();
            $table->timestamp('password_setup_at')->nullable();

            $table->foreignId('deleted_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

<?php

declare(strict_types=1);

namespace XetaSuite\Console\Commands;

use Illuminate\Console\Command;

class ResetDemoDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:reset
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reset the database to its demo state with fresh seed data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('app.demo_mode')) {
            $this->error('This command can only be run in demo mode (DEMO_MODE=true).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will reset ALL data. Are you sure?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Resetting demo database...');

        // Refresh the database with migrations and seeders
        $this->call('migrate:fresh', [
            '--force' => true,
            '--seed' => true,
        ]);

        $this->info('Demo database has been reset successfully!');
        $this->newLine();
        $this->table(
            ['Account', 'Email', 'Password'],
            [
                ['Admin', 'admin@xetasuite.demo', 'password'],
                ['Manager', 'manager@xetasuite.demo', 'password'],
                ['User', 'user@xetasuite.demo', 'password'],
            ]
        );

        return self::SUCCESS;
    }
}

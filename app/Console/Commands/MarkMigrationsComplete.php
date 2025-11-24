<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkMigrationsComplete extends Command
{
    protected $signature = 'migrate:mark-complete';
    protected $description = 'Mark manual SQL migrations as complete in Laravel';

    public function handle()
    {
        // Get the latest batch number
        $latestBatch = DB::table('migrations')->max('batch') ?? 0;
        $newBatch = $latestBatch + 1;

        // Migrations that were run manually
        $migrations = [
            '2025_11_23_235211_upgrade_users_table_structure',
            '2025_11_23_235218_create_user_addresses_table',
            '2025_11_23_235225_create_vendor_profiles_table',
            '2025_11_23_235231_create_corporate_profiles_table',
            '2025_11_23_235239_create_user_shop_access_table',
            '2025_11_23_235246_create_social_logins_table',
            '2025_11_23_235252_create_user_activity_logs_table',
        ];

        $this->info('Marking migrations as complete...');
        $this->newLine();

        foreach ($migrations as $migration) {
            // Check if already exists
            $exists = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();
            
            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $newBatch,
                ]);
                $this->info("✓ Marked {$migration} as complete");
            } else {
                $this->warn("⚠ {$migration} already exists in migrations table");
            }
        }

        $this->newLine();
        $this->info("✅ All migrations marked as complete! (Batch: {$newBatch})");
        
        return Command::SUCCESS;
    }
}

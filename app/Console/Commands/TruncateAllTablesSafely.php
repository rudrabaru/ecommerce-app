<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateAllTablesSafely extends Command
{
    protected $signature = 'db:truncate-all';
    protected $description = 'Truncate all tables safely except pivot/migration tables';

    public function handle()
    {
        $this->info('Disabling foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Tables to skip
        $safeTables = [
            'migrations', // never truncate migrations
            'password_reset_tokens', // optional, if you want to keep them
            'failed_jobs', // optional
        ];

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');

        // Flatten the result array
        $tables = array_map(function ($table) use ($dbName) {
            $key = "Tables_in_$dbName";
            return $table->$key;
        }, $tables);

        // Separate pivot tables (contain underscore and match existing table names)
        $pivotTables = [];
        foreach ($tables as $table) {
            if (substr_count($table, '_') === 1 && !in_array($table, $safeTables)) {
                $pivotTables[] = $table;
            }
        }

        // Tables to truncate: exclude safe tables
        $tablesToTruncate = array_diff($tables, $safeTables);

        // First truncate pivot tables to avoid FK constraints
        foreach ($pivotTables as $pivot) {
            $this->info("Truncating pivot table: $pivot");
            DB::table($pivot)->truncate();
        }

        // Then truncate remaining tables
        foreach ($tablesToTruncate as $table) {
            if (!in_array($table, $pivotTables)) {
                $this->info("Truncating table: $table");
                DB::table($table)->truncate();
            }
        }

        $this->info('Re-enabling foreign key checks...');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All tables truncated successfully!');
    }
}

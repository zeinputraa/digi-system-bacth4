<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-except-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all database tables except users and roles to preserve user accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning database tables...');

        Schema::disableForeignKeyConstraints();

        $tables = [
            'borrowing_returns',
            'borrowing_details',
            'borrowings',
            'incident_reports',
            'report_archives',
            'holidays',
            'product_units',
            'products',
            'categories',
            'personal_access_tokens',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("Truncated table: {$table}");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Database cleaning completed successfully!');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BaseSeeder extends Seeder
{
    /**
     * The table to check for existing records.
     *
     * @var string
     */
    protected string $table;

    /**
     * The unique column to check for existing records.
     *
     * @var string
     */
    protected string $uniqueColumn = 'id';

    /**
     * Records that this seeder will create.
     * Format: [['column' => 'value', ...], ...]
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $records = [];

    /**
     * Columns to check when determining if a record exists.
     * If empty, all columns in the record will be checked.
     *
     * @var array<int, string>
     */
    protected array $checkColumns = [];

    /**
     * Seed the application's database if records don't exist.
     */
    public function run(): void
    {
        if (!$this->shouldRun()) {
            $this->command->info("Skipping {$this->table} seeder as records already exist.");
            return;
        }

        $this->command->info("Running {$this->table} seeder...");
        $this->runSeed();
    }

    /**
     * Check if the seeder should run.
     *
     * @return bool
     */
    protected function shouldRun(): bool
    {
        // If the table doesn't exist yet, we should run the seeder
        if (!Schema::hasTable($this->table)) {
            return true;
        }

        // If no specific records are defined, check if the table is empty
        if (empty($this->records)) {
            return DB::table($this->table)->count() === 0;
        }

        // Check if any of the specific records don't exist
        foreach ($this->records as $record) {
            if (!$this->recordExists($record)) {
                return true;
            }
        }

        // All records exist, no need to run the seeder
        return false;
    }

    /**
     * Check if a specific record exists in the database.
     *
     * @param array<string, mixed> $record
     * @return bool
     */
    protected function recordExists(array $record): bool
    {
        $query = DB::table($this->table);
        
        // Determine which columns to check
        $columnsToCheck = !empty($this->checkColumns) 
            ? array_intersect_key($record, array_flip($this->checkColumns))
            : $record;
        
        // Build the query
        foreach ($columnsToCheck as $column => $value) {
            $query->where($column, $value);
        }
        
        // Check if the record exists
        return $query->exists();
    }

    /**
     * Run the actual seed logic.
     *
     * @return void
     */
    abstract protected function runSeed(): void;
}

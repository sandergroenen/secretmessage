<?php

namespace App\Database\Seeders;

use App\Models\User;

class UserSeeder extends BaseSeeder
{
    /**
     * The table to check for existing records.
     *
     * @var string
     */
    protected string $table = 'users';
    
    /**
     * Records that this seeder will create.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $records = [
        [
            'email' => 'test@example.com',
        ]
    ];
    
    /**
     * Columns to check when determining if a record exists.
     *
     * @var array<int, string>
     */
    protected array $checkColumns = ['email'];

    /**
     * Run the actual seed logic.
     *
     * @return void
     */
    protected function runSeed(): void
    {
     
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->command->info('Test user created successfully!');
    }
}

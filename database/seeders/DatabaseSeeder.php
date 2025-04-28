<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create userA if it doesn't exist
        if(User::whereEmail('userA@example.com')->count() === 0) {
            User::create([
                'name' => 'User A',
                'email' => 'userA@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        
        // Create userB if it doesn't exist
        if(User::whereEmail('userB@example.com')->count() === 0) {
            User::create([
                'name' => 'User B',
                'email' => 'userB@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}

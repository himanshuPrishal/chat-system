<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users if they don't exist
        $testUsers = [
            [
                'name' => 'Test User',
                'email' => 'testuser@example.com',
                'password' => Hash::make('Password@123'),
                'status' => 'Available for testing',
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('Password@123'),
                'status' => 'Hey there! I am using ChatApp',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('Password@123'),
                'status' => 'Busy coding',
            ],
        ];

        foreach ($testUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Test users created successfully!');
    }
}

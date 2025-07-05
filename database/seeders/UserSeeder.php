<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users with different roles
        $users = [
            [
                'name' => 'John Owner',
                'email' => 'owner@shiftend.com',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ],
            [
                'name' => 'Sarah Manager',
                'email' => 'manager@shiftend.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ],
            [
                'name' => 'Mike Employee',
                'email' => 'employee@shiftend.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ],
            [
                'name' => 'Lisa Manager',
                'email' => 'lisa@shiftend.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ],
            [
                'name' => 'Tom Employee',
                'email' => 'tom@shiftend.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(['email' => $userData['email']], $userData);

            // Update role if user already exists
            if ($user->wasRecentlyCreated === false) {
                $user->update(['role' => $userData['role']]);
            }
        }
    }
}

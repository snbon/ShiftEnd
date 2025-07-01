<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the owner user
        $owner = User::where('email', 'owner@shiftend.com')->first();

        if (!$owner) {
            // Create owner if doesn't exist
            $owner = User::create([
                'name' => 'Restaurant Owner',
                'email' => 'owner@shiftend.com',
                'password' => bcrypt('password'),
                'role' => 'owner',
                'email_verified_at' => now(),
            ]);
        }

        // Create sample locations
        $locations = [
            [
                'name' => 'Downtown Restaurant',
                'address' => '123 Main Street, Downtown, City',
                'phone' => '(555) 123-4567',
                'owner_id' => $owner->id,
                'is_active' => true,
            ],
            [
                'name' => 'Uptown Bistro',
                'address' => '456 Oak Avenue, Uptown, City',
                'phone' => '(555) 987-6543',
                'owner_id' => $owner->id,
                'is_active' => true,
            ],
            [
                'name' => 'Suburban Cafe',
                'address' => '789 Pine Road, Suburbs, City',
                'phone' => '(555) 456-7890',
                'owner_id' => $owner->id,
                'is_active' => true,
            ],
        ];

        foreach ($locations as $locationData) {
            Location::firstOrCreate([
                'name' => $locationData['name'],
                'address' => $locationData['address'],
            ], $locationData);
        }

        // Assign users to locations (avoid duplicates)
        $manager = User::where('email', 'manager@shiftend.com')->first();
        $employee = User::where('email', 'employee@shiftend.com')->first();
        $lisa = User::where('email', 'lisa@shiftend.com')->first();
        $tom = User::where('email', 'tom@shiftend.com')->first();
        $firstLocation = Location::first();

        if ($manager && $firstLocation && !$manager->locations->contains($firstLocation->id)) {
            $manager->locations()->attach($firstLocation->id, ['role' => 'manager', 'status' => 'active']);
        }
        if ($employee && $firstLocation && !$employee->locations->contains($firstLocation->id)) {
            $employee->locations()->attach($firstLocation->id, ['role' => 'employee', 'status' => 'active']);
        }
        if ($lisa && $firstLocation && !$lisa->locations->contains($firstLocation->id)) {
            $lisa->locations()->attach($firstLocation->id, ['role' => 'manager', 'status' => 'active']);
        }
        if ($tom && $firstLocation && !$tom->locations->contains($firstLocation->id)) {
            $tom->locations()->attach($firstLocation->id, ['role' => 'employee', 'status' => 'active']);
        }
    }
}

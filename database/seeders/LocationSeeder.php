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
            Location::create($locationData);
        }

        // Assign users to locations
        $manager = User::where('email', 'manager@shiftend.com')->first();
        $employee = User::where('email', 'employee@shiftend.com')->first();

        if ($manager) {
            $manager->update(['location_id' => Location::first()->id]);
        }

        if ($employee) {
            $employee->update(['location_id' => Location::first()->id]);
        }
    }
}

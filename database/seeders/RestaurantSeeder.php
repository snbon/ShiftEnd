<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users
        $owner = User::where('email', 'owner@shiftend.com')->first();
        $manager = User::where('email', 'manager@shiftend.com')->first();
        $employee = User::where('email', 'employee@shiftend.com')->first();
        $lisa = User::where('email', 'lisa@shiftend.com')->first();
        $tom = User::where('email', 'tom@shiftend.com')->first();

        // Create restaurants
        $restaurants = [
            [
                'name' => 'Downtown Bistro',
                'address' => '123 Main Street, Downtown',
                'owner_id' => $owner->id,
            ],
            [
                'name' => 'Riverside Cafe',
                'address' => '456 River Road, Riverside',
                'owner_id' => $owner->id,
            ],
        ];

        foreach ($restaurants as $restaurantData) {
            $restaurant = Restaurant::create($restaurantData);

            // Assign users to restaurants with roles
            if ($restaurant->name === 'Downtown Bistro') {
                $restaurant->users()->attach([
                    $manager->id => ['role' => 'manager'],
                    $employee->id => ['role' => 'employee'],
                    $lisa->id => ['role' => 'manager'],
                ]);
            } else {
                $restaurant->users()->attach([
                    $lisa->id => ['role' => 'manager'],
                    $tom->id => ['role' => 'employee'],
                ]);
            }
        }
    }
}

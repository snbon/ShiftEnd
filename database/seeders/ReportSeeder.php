<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all();

        if ($locations->isEmpty()) {
            return; // Skip if no locations
        }

        // Create reports for the past 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            foreach ($locations as $location) {
                $locationUsers = $location->users;

                if ($locationUsers->isEmpty()) {
                    continue;
                }

                $user = $locationUsers->random();
                $userRole = $user->pivot->role ?? 'employee';

                // Find a manager for approval if needed
                $manager = $locationUsers->first(function ($u) {
                    return $u->pivot->role === 'manager';
                });

                Report::create([
                    'location_id' => $location->id,
                    'user_id' => $user->id,
                    'report_date' => $date,
                    'shift_start_time' => '08:00',
                    'shift_end_time' => '16:00',
                    'cash_sales' => rand(200, 800),
                    'card_sales' => rand(400, 1200),
                    'total_sales' => 0, // Will be calculated
                    'opening_cash' => rand(100, 300),
                    'closing_cash' => rand(200, 600),
                    'cash_difference' => 0, // Will be calculated
                    'tips_cash' => rand(20, 100),
                    'tips_card' => rand(30, 150),
                    'total_tips' => 0, // Will be calculated
                    'inventory_notes' => $this->getRandomInventoryNotes(),
                    'shift_notes' => $this->getRandomShiftNotes(),
                    'status' => $this->getRandomStatus(),
                    'approved_by' => $userRole === 'employee' ? $manager?->id : null,
                    'approved_at' => $userRole === 'employee' ? now() : null,
                ]);
            }
        }

        // Update calculated fields
        Report::all()->each(function ($report) {
            $report->update([
                'total_sales' => $report->cash_sales + $report->card_sales,
                'total_tips' => $report->tips_cash + $report->tips_card,
                'cash_difference' => $report->closing_cash - $report->opening_cash - $report->cash_sales - $report->tips_cash,
            ]);
        });
    }

    private function getRandomInventoryNotes()
    {
        $notes = [
            'Some bread went stale',
            'Leftover vegetables disposed',
            'No waste today',
            'Small amount of pasta waste',
            'Milk expired',
            'Fresh produce restocked',
            null,
        ];

        return $notes[array_rand($notes)];
    }

    private function getRandomShiftNotes()
    {
        $notes = [
            'Busy lunch shift',
            'Slow evening, good for cleaning',
            'New menu items well received',
            'Staff meeting went well',
            'Equipment maintenance needed',
            'Great team work today',
            'Customer feedback was positive',
            null,
        ];

        return $notes[array_rand($notes)];
    }

    private function getRandomStatus()
    {
        $statuses = ['draft', 'submitted', 'approved'];
        return $statuses[array_rand($statuses)];
    }
}

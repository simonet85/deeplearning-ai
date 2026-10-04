<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (User::where('role', Role::Therapist)->get() as $therapist) {
            foreach ([1, 2, 3] as $daysAhead) {
                foreach (['09:00', '11:00', '14:00'] as $slot) {
                    Availability::create([
                        'therapist_id' => $therapist->id,
                        'date' => now()->addDays($daysAhead)->toDateString(),
                        'time_slot' => $slot,
                    ]);
                }
            }
        }
    }
}

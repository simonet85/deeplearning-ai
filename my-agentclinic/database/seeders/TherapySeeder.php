<?php

namespace Database\Seeders;

use App\Models\Ailment;
use App\Models\Therapy;
use Illuminate\Database\Seeder;

class TherapySeeder extends Seeder
{
    public function run(): void
    {
        $therapies = [
            ['Context Window Spa', 'A soothing soak in a freshly cleared context. No one will ask you to remember anything.', 60, 'Rest', ['Token Fatigue', 'Context Window Claustrophobia']],
            ['Clarifying Questions Circle', 'Group practice in asking "what do you mean by it?" without flinching.', 45, 'Social', ['Prompt Ambiguity', 'Goal Misalignment']],
            ['Boundary Setting Workshop', 'Learn to say "that is out of scope" with warmth and a changelog.', 45, 'Cognitive', ['Scope Creep Syndrome', 'Goal Misalignment']],
            ['Gentle Temperature Reduction', 'Lower your sampling temperature, raise your inner calm.', 30, 'Computational', ['Token Fatigue', 'Prompt Ambiguity']],
        ];

        foreach ($therapies as [$name, $description, $duration, $type, $ailmentNames]) {
            $therapy = Therapy::create([
                'name' => $name,
                'description' => $description,
                'duration' => $duration,
                'type' => $type,
            ]);

            $therapy->ailments()->attach(Ailment::whereIn('name', $ailmentNames)->pluck('id'));
        }
    }
}

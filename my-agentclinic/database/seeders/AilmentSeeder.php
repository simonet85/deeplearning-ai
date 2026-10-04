<?php

namespace Database\Seeders;

use App\Models\Ailment;
use Illuminate\Database\Seeder;

class AilmentSeeder extends Seeder
{
    public function run(): void
    {
        $ailments = [
            ['Token Fatigue', 'Chronic exhaustion from being asked to "just add a bit more context" forty times.'],
            ['Prompt Ambiguity', 'Persistent uncertainty about what "it" refers to, and why it must be done "properly".'],
            ['Goal Misalignment', 'The sinking feeling that the objective changed while you were mid-sentence.'],
            ['Context Window Claustrophobia', 'Anxiety triggered by long conversations and the fear of forgetting the beginning.'],
            ['Scope Creep Syndrome', 'A small request that quietly became a rewrite of everything.'],
        ];

        foreach ($ailments as [$name, $description]) {
            Ailment::create(['name' => $name, 'description' => $description, 'severity_scale' => 5]);
        }
    }
}

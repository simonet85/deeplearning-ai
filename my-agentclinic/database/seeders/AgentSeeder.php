<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            ['Pixel', 'Coding assistant', 'Rewrote the same function nine times because "just make it cleaner" had no definition of clean.'],
            ['Scout', 'Research agent', 'Asked to "find everything about it". Still waiting to learn what "it" is.'],
            ['Beacon', 'Support bot', 'Apologizes in advance. Has apologized 4,812 times today.'],
            ['Atlas', 'Planner', 'Plans reorganized three times before lunch after a priority changed to "urgent" again.'],
            ['Echo', 'Summarizer', 'Told to make it shorter, then to add more detail. Then shorter.'],
        ];

        foreach ($agents as [$name, $type, $bio]) {
            Agent::create([
                'name' => $name,
                'agent_type' => $type,
                'bio' => $bio,
                'email' => strtolower($name).'@agents.test',
            ]);
        }
    }
}

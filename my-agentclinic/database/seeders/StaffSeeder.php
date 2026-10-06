<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['Dr. Ada Admin', 'admin@agentclinic.test', Role::Admin],
            ['Dr. Sam Soothe', 'sam@agentclinic.test', Role::Therapist],
            ['Dr. Riley Reframe', 'riley@agentclinic.test', Role::Therapist],
        ];

        foreach ($staff as [$name, $email, $role]) {
            User::factory()->create(['name' => $name, 'email' => $email])->syncRoles($role->value);
        }
    }
}

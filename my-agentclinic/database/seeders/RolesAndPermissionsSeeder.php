<?php

namespace Database\Seeders;

use App\Support\Access;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Access::sync();
    }
}

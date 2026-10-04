<?php

use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles move from the `users.role` column to Spatie's tables: create the roles and permissions, give each user
     * the role their old value named, then drop the column.
     */
    public function up(): void
    {
        Access::sync();

        foreach (DB::table('users')->get(['id', 'role']) as $row) {
            User::findOrFail($row->id)->assignRole($row->role);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('therapist')->after('password');
        });

        foreach (User::with('roles')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update(['role' => $user->roles->first()?->name ?? 'therapist']);
        }
    }
};

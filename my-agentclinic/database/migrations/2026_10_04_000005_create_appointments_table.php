<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('email')->nullable()->after('bio');
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('therapy_id')->constrained()->cascadeOnDelete();
            // Nulled on cancel so the slot can be booked again; unique so a slot is booked at most once.
            $table->foreignId('availability_id')->nullable()->unique()->constrained('availability')->restrictOnDelete();
            $table->dateTime('datetime');
            $table->string('status')->default('booked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};

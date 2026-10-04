<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('ailment_therapy', function (Blueprint $table) {
            $table->foreignId('ailment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapy_id')->constrained()->cascadeOnDelete();
            $table->primary(['ailment_id', 'therapy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ailment_therapy');
        Schema::dropIfExists('therapies');
    }
};

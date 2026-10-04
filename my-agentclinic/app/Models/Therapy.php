<?php

namespace App\Models;

use Database\Factories\TherapyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'duration', 'type'])]
class Therapy extends Model
{
    /** @use HasFactory<TherapyFactory> */
    use HasFactory;

    /** @return HasMany<TherapyRating, $this> */
    public function ratings(): HasMany
    {
        return $this->hasMany(TherapyRating::class);
    }

    /** @return BelongsToMany<Ailment, $this> */
    public function ailments(): BelongsToMany
    {
        return $this->belongsToMany(Ailment::class);
    }
}

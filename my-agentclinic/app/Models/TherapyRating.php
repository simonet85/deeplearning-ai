<?php

namespace App\Models;

use Database\Factories\TherapyRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agent_id', 'therapy_id', 'rating'])]
class TherapyRating extends Model
{
    /** @use HasFactory<TherapyRatingFactory> */
    use HasFactory;

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return BelongsTo<Therapy, $this> */
    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }
}

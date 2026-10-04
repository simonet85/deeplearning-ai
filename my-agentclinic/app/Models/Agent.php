<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'agent_type', 'bio', 'email'])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<TherapyRating, $this> */
    public function therapyRatings(): HasMany
    {
        return $this->hasMany(TherapyRating::class);
    }

    /** @return HasMany<AgentAilment, $this> */
    public function agentAilments(): HasMany
    {
        return $this->hasMany(AgentAilment::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\AilmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'severity_scale'])]
class Ailment extends Model
{
    /** @use HasFactory<AilmentFactory> */
    use HasFactory;

    /** @return BelongsToMany<Therapy, $this> */
    public function therapies(): BelongsToMany
    {
        return $this->belongsToMany(Therapy::class);
    }

    /** @return HasMany<AgentAilment, $this> */
    public function agentAilments(): HasMany
    {
        return $this->hasMany(AgentAilment::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\AgentAilmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agent_id', 'ailment_id', 'severity', 'notes'])]
class AgentAilment extends Model
{
    /** @use HasFactory<AgentAilmentFactory> */
    use HasFactory;

    protected $table = 'agent_ailment';

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return BelongsTo<Ailment, $this> */
    public function ailment(): BelongsTo
    {
        return $this->belongsTo(Ailment::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'agent_type', 'bio'])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;
}

<?php

namespace App\Models;

use App\Support\Locales;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'agent_type', 'bio', 'email'])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    /**
     * The language of the e-mails this agent receives: the one chosen on their account, or the default when they have
     * no account or have not chosen. It does not depend on who is using the app when the e-mail goes out.
     */
    public function mailLocale(): string
    {
        $locale = $this->user?->locale;

        return Locales::isSupported($locale) ? $locale : Locales::default();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

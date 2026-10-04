<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['agent_id', 'therapist_id', 'therapy_id', 'availability_id', 'datetime', 'status', 'reminder_sent_at'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
            'status' => AppointmentStatus::class,
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * Booked appointments starting within the next 24 hours whose agent can be e-mailed and who has
     * not been reminded yet. Appointments booked inside that window are left to the confirmation e-mail.
     *
     * @param  Builder<Appointment>  $query
     */
    #[Scope]
    protected function needsReminder(Builder $query): void
    {
        $query->where('status', AppointmentStatus::Booked)
            ->whereNull('reminder_sent_at')
            ->where('datetime', '>', now())
            ->where('datetime', '<=', now()->addDay())
            ->whereRaw("created_at <= datetime - interval '24 hours'")
            ->whereHas('agent', fn (Builder $agent) => $agent->whereNotNull('email'));
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /** @return BelongsTo<Therapy, $this> */
    public function therapy(): BelongsTo
    {
        return $this->belongsTo(Therapy::class);
    }

    /** @return BelongsTo<Availability, $this> */
    public function availability(): BelongsTo
    {
        return $this->belongsTo(Availability::class);
    }
}

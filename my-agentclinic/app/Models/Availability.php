<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable(['therapist_id', 'date', 'time_slot'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected $table = 'availability';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /** @return HasOne<Appointment, $this> */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    /**
     * Slots that are not booked and have not started yet, soonest first.
     *
     * @return Collection<int, Availability>
     */
    public static function open(): Collection
    {
        return static::with('therapist')
            ->whereDoesntHave('appointment')
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time_slot')
            ->orderBy('therapist_id')
            ->get()
            ->reject(fn (Availability $slot) => $slot->startsAt()->isPast());
    }

    public function startsAt(): CarbonInterface
    {
        return $this->date->copy()->setTimeFromTimeString($this->time_slot);
    }
}

<?php

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $agentId = null;

    public ?int $therapyId = null;

    public ?int $availabilityId = null;

    /** @return Collection<int, Agent> */
    #[Computed]
    public function agents(): Collection
    {
        return Agent::orderBy('name')->get();
    }

    /** @return Collection<int, Therapy> */
    #[Computed]
    public function therapies(): Collection
    {
        return Therapy::orderBy('name')->get();
    }

    /** @return Collection<int, Availability> */
    #[Computed]
    public function openSlots(): Collection
    {
        return Availability::with('therapist')
            ->whereDoesntHave('appointment')
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get()
            ->reject(fn (Availability $slot) => $slot->startsAt()->isPast());
    }

    /** @return Collection<int, Appointment> */
    #[Computed]
    public function appointments(): Collection
    {
        return Appointment::with(['agent', 'therapist', 'therapy'])->orderBy('datetime')->get();
    }

    public function book(): void
    {
        $this->validate([
            'agentId' => ['required', 'exists:agents,id'],
            'therapyId' => ['required', 'exists:therapies,id'],
            'availabilityId' => ['required', 'exists:availability,id'],
        ]);

        DB::transaction(function () {
            $slot = Availability::lockForUpdate()->findOrFail($this->availabilityId);

            if ($slot->appointment()->exists() || $slot->startsAt()->isPast()) {
                throw ValidationException::withMessages([
                    'availabilityId' => __('That slot is no longer available.'),
                ]);
            }

            Appointment::create([
                'agent_id' => $this->agentId,
                'therapist_id' => $slot->therapist_id,
                'therapy_id' => $this->therapyId,
                'availability_id' => $slot->id,
                'datetime' => $slot->startsAt(),
                'status' => AppointmentStatus::Booked,
            ]);
        });

        $this->reset('agentId', 'therapyId', 'availabilityId');
        unset($this->openSlots, $this->appointments);
    }

    public function cancel(int $id): void
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->status === AppointmentStatus::Booked) {
            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'availability_id' => null,
            ]);
        }

        unset($this->openSlots, $this->appointments);
    }
}; ?>

<div class="space-y-8">
    <form wire:submit="book" class="grid gap-4 sm:grid-cols-3">
        <h3 class="text-lg font-semibold text-gray-800 sm:col-span-3">{{ __('Book an appointment') }}</h3>

        <div>
            <x-input-label for="agentId" :value="__('Patient')" />
            <select id="agentId" wire:model="agentId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select an agent') }}</option>
                @foreach ($this->agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('agentId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="therapyId" :value="__('Therapy')" />
            <select id="therapyId" wire:model="therapyId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select a therapy') }}</option>
                @foreach ($this->therapies as $therapy)
                    <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('therapyId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="availabilityId" :value="__('Open slot')" />
            <select id="availabilityId" wire:model="availabilityId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select a slot') }}</option>
                @foreach ($this->openSlots as $slot)
                    <option value="{{ $slot->id }}">{{ $slot->date->format('D, M j') }} · {{ $slot->time_slot }} · {{ $slot->therapist->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('availabilityId')" class="mt-2" />
        </div>

        <div class="sm:col-span-3">
            <x-primary-button>{{ __('Book appointment') }}</x-primary-button>
        </div>
    </form>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Appointments') }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->appointments as $appointment)
                <li wire:key="appointment-{{ $appointment->id }}" class="rounded-lg border border-gray-200 p-4">
                    <div class="font-semibold text-gray-900">{{ $appointment->agent->name }}</div>
                    <div class="text-xs uppercase tracking-wide text-indigo-600">{{ $appointment->datetime->format('D, M j · H:i') }} · {{ ucfirst($appointment->status->value) }}</div>
                    <p class="mt-2 text-sm text-gray-600">{{ $appointment->therapy->name }} {{ __('with') }} {{ $appointment->therapist->name }}</p>
                    @if ($appointment->status === \App\Enums\AppointmentStatus::Booked)
                        <div class="mt-3">
                            <x-secondary-button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="{{ __('Cancel this appointment?') }}">{{ __('Cancel') }}</x-secondary-button>
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('No appointments yet. Everyone is coping beautifully, or avoiding the couch.') }}</li>
            @endforelse
        </ul>
    </div>
</div>

<?php

use App\Actions\BookAppointment;
use App\Actions\CancelAppointment;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $agentId = null;

    public ?int $therapyId = null;

    public ?int $availabilityId = null;

    public ?int $filterAgentId = null;

    public ?int $filterTherapistId = null;

    public ?int $filterTherapyId = null;

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
        return Availability::open();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function therapists(): Collection
    {
        return User::permission('availability.manage')->orderBy('name')->get();
    }

    /** @return Collection<int, Appointment> */
    #[Computed]
    public function upcoming(): Collection
    {
        return $this->filtered()->where('datetime', '>=', now())->orderBy('datetime')->get();
    }

    /** @return Collection<int, Appointment> */
    #[Computed]
    public function past(): Collection
    {
        return $this->filtered()->where('datetime', '<', now())->orderByDesc('datetime')->get();
    }

    public function isFiltered(): bool
    {
        return $this->filterAgentId || $this->filterTherapistId || $this->filterTherapyId;
    }

    public function clearFilters(): void
    {
        $this->reset('filterAgentId', 'filterTherapistId', 'filterTherapyId');
    }

    /** @return Builder<Appointment> */
    private function filtered(): Builder
    {
        return Appointment::with(['agent', 'therapist', 'therapy'])
            ->when($this->filterAgentId, fn ($query, $id) => $query->where('agent_id', $id))
            ->when($this->filterTherapistId, fn ($query, $id) => $query->where('therapist_id', $id))
            ->when($this->filterTherapyId, fn ($query, $id) => $query->where('therapy_id', $id));
    }

    public function book(): void
    {
        abort_unless(auth()->user()->can('appointments.manage'), 403);

        $this->validate([
            'agentId' => ['required', 'exists:agents,id'],
            'therapyId' => ['required', 'exists:therapies,id'],
            'availabilityId' => ['required', 'exists:availability,id'],
        ]);

        app(BookAppointment::class)->handle(
            Agent::findOrFail($this->agentId),
            Therapy::findOrFail($this->therapyId),
            $this->availabilityId,
        );

        $this->reset('agentId', 'therapyId', 'availabilityId');
        unset($this->openSlots, $this->upcoming, $this->past);
        $this->dispatch('appointments-changed');
    }

    public function cancel(int $id): void
    {
        abort_unless(auth()->user()->can('appointments.manage'), 403);

        app(CancelAppointment::class)->handle(Appointment::findOrFail($id));

        unset($this->openSlots, $this->upcoming, $this->past);
        $this->dispatch('appointments-changed');
    }
}; ?>

<div class="space-y-8">
    @can('appointments.manage')
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
                    <option value="{{ $slot->id }}">{{ $slot->date->localized('short') }} · {{ $slot->time_slot }} · {{ $slot->therapist->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('availabilityId')" class="mt-2" />
        </div>

        <div class="sm:col-span-3">
            <x-primary-button>{{ __('Book appointment') }}</x-primary-button>
        </div>
    </form>
    @endcan

    <div class="grid gap-4 sm:grid-cols-4">
        <div>
            <x-input-label for="filterAgentId" :value="__('Filter by patient')" />
            <select id="filterAgentId" wire:model.live="filterAgentId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All agents') }}</option>
                @foreach ($this->agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="filterTherapistId" :value="__('Filter by therapist')" />
            <select id="filterTherapistId" wire:model.live="filterTherapistId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All therapists') }}</option>
                @foreach ($this->therapists as $therapist)
                    <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="filterTherapyId" :value="__('Filter by therapy')" />
            <select id="filterTherapyId" wire:model.live="filterTherapyId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All therapies') }}</option>
                @foreach ($this->therapies as $therapy)
                    <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                @endforeach
            </select>
        </div>

        @if ($this->isFiltered())
            <div class="flex items-end">
                <x-secondary-button type="button" wire:click="clearFilters">{{ __('Clear filters') }}</x-secondary-button>
            </div>
        @endif
    </div>

    @foreach ([__('Upcoming appointments') => $this->upcoming, __('Past appointments') => $this->past] as $heading => $appointments)
    @if ($appointments->isNotEmpty() || $heading === __('Upcoming appointments'))
    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ $heading }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($appointments as $appointment)
                <li wire:key="appointment-{{ $appointment->id }}" class="rounded-lg border border-line bg-surface-raised p-4">
                    <div class="font-semibold text-gray-900">{{ $appointment->agent->name }}</div>
                    <div class="text-xs uppercase tracking-[0.08em] text-indigo-600">{{ $appointment->datetime->localized('datetime') }} · {{ __(ucfirst($appointment->status->value)) }}</div>
                    <p class="mt-2 text-sm text-gray-600">{{ $appointment->therapy->name }} {{ __('with') }} {{ $appointment->therapist->name }}</p>
                    @if ($appointment->reminder_sent_at)
                        <p class="mt-1 text-xs text-gray-500">{{ __('Reminder sent') }} {{ $appointment->reminder_sent_at->localized('stamp') }}</p>
                    @endif
                    @if ($appointment->status === \App\Enums\AppointmentStatus::Booked && auth()->user()->can('appointments.manage'))
                        <div class="mt-3">
                            <x-secondary-button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="{{ __('Cancel this appointment?') }}">{{ __('Cancel') }}</x-secondary-button>
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">
                    {{ $this->isFiltered() ? __('No appointments match those filters.') : __('No appointments yet. Everyone is coping beautifully, or avoiding the couch.') }}
                </li>
            @endforelse
        </ul>
    </div>
    @endif
    @endforeach
</div>

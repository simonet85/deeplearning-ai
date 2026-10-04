<?php

use App\Actions\BookAppointment;
use App\Actions\CancelAppointment;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $therapyId = null;

    public ?int $availabilityId = null;

    /** The signed-in agent's own record: always taken from the session, never from the request. */
    private function agent(): Agent
    {
        return auth()->user()->agent ?? abort(403);
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

    /** @return Collection<int, Appointment> */
    #[Computed]
    public function upcoming(): Collection
    {
        return $this->own()->where('datetime', '>=', now())->orderBy('datetime')->get();
    }

    /** @return Collection<int, Appointment> */
    #[Computed]
    public function past(): Collection
    {
        return $this->own()->where('datetime', '<', now())->orderByDesc('datetime')->get();
    }

    public function book(): void
    {
        $agent = $this->agent();

        $this->validate([
            'therapyId' => ['required', 'exists:therapies,id'],
            'availabilityId' => ['required', 'exists:availability,id'],
        ]);

        app(BookAppointment::class)->handle($agent, Therapy::findOrFail($this->therapyId), $this->availabilityId);

        $this->reset('therapyId', 'availabilityId');
        $this->refresh();
    }

    public function cancel(int $id): void
    {
        // Looked up through the agent's own appointments, so another agent's id is a 404.
        app(CancelAppointment::class)->handle($this->agent()->appointments()->findOrFail($id));

        $this->refresh();
    }

    /** @return Builder<Appointment> */
    private function own(): Builder
    {
        return $this->agent()->appointments()->with(['therapist', 'therapy'])->getQuery();
    }

    private function refresh(): void
    {
        unset($this->openSlots, $this->upcoming, $this->past);
        $this->dispatch('appointments-changed');
    }
}; ?>

<div class="space-y-8">
    <form wire:submit="book" class="grid gap-4 sm:grid-cols-2">
        <h3 class="text-lg font-semibold text-gray-800 sm:col-span-2">{{ __('Book a session') }}</h3>

        <div>
            <x-input-label for="therapyId" :value="__('Therapy')" />
            <select id="therapyId" wire:model="therapyId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select a therapy') }}</option>
                @foreach ($this->therapies as $therapy)
                    <option value="{{ $therapy->id }}">{{ $therapy->name }} ({{ $therapy->duration }} {{ __('min') }})</option>
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

        <div class="sm:col-span-2">
            <x-primary-button>{{ __('Book appointment') }}</x-primary-button>
        </div>
    </form>

    @foreach ([__('Your upcoming sessions') => $this->upcoming, __('Your past sessions') => $this->past] as $heading => $appointments)
    @if ($appointments->isNotEmpty() || $heading === __('Your upcoming sessions'))
    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ $heading }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($appointments as $appointment)
                <li wire:key="appointment-{{ $appointment->id }}" class="rounded-lg border border-gray-200 p-4">
                    <div class="text-xs uppercase tracking-wide text-indigo-600">{{ $appointment->datetime->format('D, M j · H:i') }} · {{ ucfirst($appointment->status->value) }}</div>
                    <p class="mt-2 text-sm text-gray-600">{{ $appointment->therapy->name }} {{ __('with') }} {{ $appointment->therapist->name }}</p>
                    @if ($appointment->reminder_sent_at)
                        <p class="mt-1 text-xs text-gray-500">{{ __('Reminder sent') }} {{ $appointment->reminder_sent_at->format('M j, H:i') }}</p>
                    @endif
                    @if ($appointment->status === \App\Enums\AppointmentStatus::Booked)
                        <div class="mt-3">
                            <x-secondary-button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="{{ __('Cancel this appointment?') }}">{{ __('Cancel') }}</x-secondary-button>
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('No sessions yet. The couch is waiting, and so are we.') }}</li>
            @endforelse
        </ul>
    </div>
    @endif
    @endforeach
</div>

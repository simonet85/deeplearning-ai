<?php

use App\Models\Availability;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $therapistId = null;

    public string $date = '';

    public string $timeSlot = '';

    /** @return Collection<int, User> */
    #[Computed]
    public function therapists(): Collection
    {
        return User::permission('availability.manage')->orderBy('name')->get();
    }

    /** @return Collection<int, Availability> */
    #[Computed]
    public function slots(): Collection
    {
        return Availability::with(['therapist', 'appointment'])
            ->when(! auth()->user()->can('availability.manage-all'), fn ($query) => $query->where('therapist_id', auth()->id()))
            ->orderBy('date')
            ->orderBy('time_slot')
            ->orderBy('therapist_id')
            ->get();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->canAny(['availability.manage', 'availability.manage-all']), 403);

        $therapistId =auth()->user()->can('availability.manage-all') ? $this->therapistId : auth()->id();

        $validated = $this->validate([
            'therapistId' => [
                Rule::requiredIf(auth()->user()->can('availability.manage-all')),
                'nullable',
                Rule::exists('users', 'id')->whereIn('id', User::permission('availability.manage')->select('users.id')),
            ],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'timeSlot' => [
                'required',
                'date_format:H:i',
                Rule::unique('availability', 'time_slot')
                    ->where('therapist_id', $therapistId)
                    ->where('date', $this->date),
            ],
        ]);

        Availability::create([
            'therapist_id' => $therapistId,
            'date' => $validated['date'],
            'time_slot' => $validated['timeSlot'],
        ]);

        $this->reset('therapistId', 'date', 'timeSlot');
        unset($this->slots);
    }

    public function remove(int $id): void
    {
        $slot = Availability::findOrFail($id);

        $user = auth()->user();

        abort_unless(
            $user->can('availability.manage-all') || ($user->can('availability.manage') && $slot->therapist_id === $user->id),
            403,
        );

        if ($slot->appointment()->exists()) {
            $this->addError('slot', __('This slot has a booked appointment. Cancel it first.'));

            return;
        }

        $slot->delete();
        unset($this->slots);
    }
}; ?>

<div class="space-y-8">
    @canany(['availability.manage', 'availability.manage-all'])
    <form wire:submit="save" class="grid gap-4 sm:grid-cols-3">
        @if (auth()->user()->can('availability.manage-all'))
            <div class="sm:col-span-3">
                <x-input-label for="therapistId" :value="__('Therapist')" />
                <select id="therapistId" wire:model="therapistId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('Select a therapist') }}</option>
                    @foreach ($this->therapists as $therapist)
                        <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('therapistId')" class="mt-2" />
            </div>
        @endif

        <div class="sm:col-span-2">
            <x-input-label for="date" :value="__('Date')" />
            <x-text-input id="date" type="date" wire:model="date" class="touch-target mt-1 block w-full" />
            <x-input-error :messages="$errors->get('date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="timeSlot" :value="__('Time')" />
            <x-text-input id="timeSlot" type="time" wire:model="timeSlot" class="touch-target mt-1 block w-full" />
            <x-input-error :messages="$errors->get('timeSlot')" class="mt-2" />
        </div>

        <div class="sm:col-span-3">
            <x-primary-button>{{ __('Add slot') }}</x-primary-button>
        </div>
    </form>
    @endcanany

    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Availability calendar') }}</h3>
        <x-input-error :messages="$errors->get('slot')" class="mt-2" />
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->slots->groupBy(fn ($slot) => $slot->date->toDateString()) as $day => $daySlots)
                <section wire:key="day-{{ $day }}" class="rounded-lg border border-gray-200">
                    <h4 class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-800">{{ $daySlots->first()->date->localized('short') }}</h4>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($daySlots as $slot)
                            <li wire:key="slot-{{ $slot->id }}" class="flex items-center justify-between gap-2 px-4 py-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $slot->time_slot }}</div>
                                    <div class="text-xs uppercase tracking-[0.08em] text-indigo-600">{{ $slot->therapist->name }}</div>
                                </div>
                                @if ($slot->appointment)
                                    <span class="rounded-full bg-scrub-soft px-2.5 py-0.5 text-xs font-semibold text-scrub">{{ __('Booked') }}</span>
                                @elseif (auth()->user()->canAny(['availability.manage', 'availability.manage-all']))
                                    <x-danger-button type="button" wire:click="remove({{ $slot->id }})" wire:confirm="{{ __('Remove this slot?') }}">{{ __('Remove') }}</x-danger-button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <p class="text-sm text-gray-500 sm:col-span-2 lg:col-span-3">{{ __('No open slots. The couch sits empty, unbooked and slightly judgmental.') }}</p>
            @endforelse
        </div>
    </div>
</div>

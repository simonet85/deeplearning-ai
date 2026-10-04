<?php

use App\Models\Agent;
use App\Models\Ailment;
use App\Models\Therapy;
use App\Models\TherapyRating;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public ?int $duration = null;

    public string $type = '';

    /** @var array<int, int|string> */
    public array $ailmentIds = [];

    public ?int $ratingAgentId = null;

    public ?int $ratingTherapyId = null;

    public ?int $ratingValue = null;

    /** @return Collection<int, Therapy> */
    #[Computed]
    public function therapies(): Collection
    {
        return Therapy::with('ailments')
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Agent> */
    #[Computed]
    public function agents(): Collection
    {
        return Agent::orderBy('name')->get();
    }

    public function rate(): void
    {
        $validated = $this->validate([
            'ratingAgentId' => ['required', 'exists:agents,id'],
            'ratingTherapyId' => ['required', 'exists:therapies,id'],
            'ratingValue' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        TherapyRating::updateOrCreate(
            ['agent_id' => $validated['ratingAgentId'], 'therapy_id' => $validated['ratingTherapyId']],
            ['rating' => $validated['ratingValue']],
        );

        $this->reset('ratingAgentId', 'ratingTherapyId', 'ratingValue');
        unset($this->therapies);
    }

    /** @return Collection<int, Ailment> */
    #[Computed]
    public function ailments(): Collection
    {
        return Ailment::orderBy('name')->get();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('therapies', 'name')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration' => ['required', 'integer', 'min:5', 'max:480'],
            'type' => ['required', 'string', 'max:50'],
            'ailmentIds' => ['array'],
            'ailmentIds.*' => ['exists:ailments,id'],
        ]);

        $therapy = Therapy::findOrNew($this->editingId);
        $therapy->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'duration' => $validated['duration'],
            'type' => $validated['type'],
        ])->save();
        $therapy->ailments()->sync($validated['ailmentIds']);

        $this->cancel();
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $therapy = Therapy::with('ailments')->findOrFail($id);

        $this->editingId = $therapy->id;
        $this->name = $therapy->name;
        $this->description = (string) $therapy->description;
        $this->duration = $therapy->duration;
        $this->type = $therapy->type;
        $this->ailmentIds = $therapy->ailments->pluck('id')->all();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        Therapy::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }

        unset($this->therapies);
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'description', 'duration', 'type', 'ailmentIds');
        $this->resetValidation();
        unset($this->therapies);
    }
}; ?>

<div class="space-y-8">
    @if (auth()->user()->isAdmin())
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <h3 class="text-lg font-semibold text-gray-800 sm:col-span-2">
                {{ $editingId ? __('Edit therapy') : __('Add a therapy') }}
            </h3>

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" type="text" wire:model="name" class="touch-target mt-1 block w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Type')" />
                <x-text-input id="type" type="text" wire:model="type" class="touch-target mt-1 block w-full" />
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="duration" :value="__('Duration (minutes)')" />
                <x-text-input id="duration" type="number" min="5" wire:model="duration" class="touch-target mt-1 block w-full" />
                <x-input-error :messages="$errors->get('duration')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <fieldset class="sm:col-span-2">
                <legend class="text-sm font-medium text-gray-700">{{ __('Treats these ailments') }}</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->ailments as $ailment)
                        <label wire:key="ailment-option-{{ $ailment->id }}" class="touch-target flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="ailmentIds" value="{{ $ailment->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            {{ $ailment->name }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="collect($errors->get('ailmentIds.*'))->flatten()->all()"class="mt-2" />
            </fieldset>

            <div class="flex flex-wrap gap-2 sm:col-span-2">
                <x-primary-button>{{ $editingId ? __('Save therapy') : __('Add therapy') }}</x-primary-button>
                @if ($editingId)
                    <x-secondary-button type="button" wire:click="cancel">{{ __('Cancel') }}</x-secondary-button>
                @endif
            </div>
        </form>
    @endif

    <form wire:submit="rate" class="grid gap-4 sm:grid-cols-3">
        <h3 class="text-lg font-semibold text-gray-800 sm:col-span-3">{{ __('Rate a therapy') }}</h3>

        <div>
            <x-input-label for="ratingAgentId" :value="__('Patient')" />
            <select id="ratingAgentId" wire:model="ratingAgentId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select an agent') }}</option>
                @foreach ($this->agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('ratingAgentId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="ratingTherapyId" :value="__('Therapy')" />
            <select id="ratingTherapyId" wire:model="ratingTherapyId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select a therapy') }}</option>
                @foreach ($this->therapies as $therapy)
                    <option value="{{ $therapy->id }}">{{ $therapy->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('ratingTherapyId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="ratingValue" :value="__('Rating (1-5)')" />
            <x-text-input id="ratingValue" type="number" min="1" max="5" wire:model="ratingValue" class="touch-target mt-1 block w-full" />
            <x-input-error :messages="$errors->get('ratingValue')" class="mt-2" />
        </div>

        <div class="sm:col-span-3">
            <x-primary-button>{{ __('Submit rating') }}</x-primary-button>
        </div>
    </form>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Therapy catalog') }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->therapies as $therapy)
                <li wire:key="therapy-{{ $therapy->id }}" class="rounded-lg border border-gray-200 p-4">
                    <div class="font-semibold text-gray-900">{{ $therapy->name }}</div>
                    <div class="text-xs uppercase tracking-wide text-indigo-600">{{ $therapy->type }} · {{ $therapy->duration }} {{ __('min') }}</div>
                    <p class="mt-2 text-sm text-gray-600">{{ $therapy->description }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-700">
                        @if ($therapy->ratings_count > 0)
                            {{ __('Rated :avg/5 (:count)', ['avg' => number_format($therapy->ratings_avg_rating, 1), 'count' => $therapy->ratings_count]) }}
                        @else
                            {{ __('No ratings yet') }}
                        @endif
                    </p>
                    @if ($therapy->ailments->isNotEmpty())
                        <p class="mt-2 text-xs text-gray-500">{{ __('Treats:') }} {{ $therapy->ailments->pluck('name')->sort()->join(', ') }}</p>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <div class="mt-3 flex gap-2">
                            <x-secondary-button type="button" wire:click="edit({{ $therapy->id }})">{{ __('Edit') }}</x-secondary-button>
                            <x-danger-button type="button" wire:click="delete({{ $therapy->id }})" wire:confirm="{{ __('Delete this therapy?') }}">{{ __('Delete') }}</x-danger-button>
                        </div>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('The catalog is empty. Healing is on hold until someone writes a prescription.') }}</li>
            @endforelse
        </ul>
    </div>
</div>

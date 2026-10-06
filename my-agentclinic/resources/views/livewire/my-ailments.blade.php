<?php

use App\Models\Agent;
use App\Models\AgentAilment;
use App\Models\Ailment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $ailmentId = null;

    public ?int $severity = null;

    public string $notes = '';

    /** The signed-in agent's own record: always taken from the session, never from the request. */
    private function agent(): Agent
    {
        return auth()->user()->agent ?? abort(403);
    }

    /** @return Collection<int, Ailment> */
    #[Computed]
    public function ailments(): Collection
    {
        return Ailment::orderBy('name')->get();
    }

    /** @return Collection<int, AgentAilment> */
    #[Computed]
    public function records(): Collection
    {
        return $this->agent()->agentAilments()->with('ailment')->latest('id')->get();
    }

    public function save(): void
    {
        $agent = $this->agent();

        $validated = $this->validate([
            'ailmentId' => ['required', 'exists:ailments,id'],
            'severity' => ['required', 'integer', 'min:1', 'max:'.(Ailment::find($this->ailmentId)?->severity_scale ?? 5)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent->agentAilments()->create([
            'ailment_id' => $validated['ailmentId'],
            'severity' => $validated['severity'],
            'notes' => $validated['notes'] ?: null,
        ]);

        $this->reset('ailmentId', 'severity', 'notes');
        unset($this->records);
    }
}; ?>

<div class="space-y-8">
    <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
        <h3 class="text-lg font-semibold text-gray-800 sm:col-span-2">{{ __('What is weighing on you?') }}</h3>

        <div>
            <x-input-label for="ailmentId" :value="__('Ailment')" />
            <select id="ailmentId" wire:model="ailmentId" class="touch-target mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Select an ailment') }}</option>
                @foreach ($this->ailments as $ailment)
                    <option value="{{ $ailment->id }}">{{ $ailment->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('ailmentId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="severity" :value="__('Severity (1 = mild, 5 = existential)')" />
            <x-text-input id="severity" type="number" min="1" max="5" wire:model="severity" class="touch-target mt-1 block w-full" />
            <x-input-error :messages="$errors->get('severity')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-primary-button>{{ __('Record ailment') }}</x-primary-button>
        </div>
    </form>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Your ailments') }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->records as $record)
                <li wire:key="record-{{ $record->id }}" class="rounded-lg border border-line bg-surface-raised p-4">
                    <div class="font-semibold text-gray-900">{{ $record->ailment->name }}</div>
                    <div class="text-xs uppercase tracking-[0.08em] text-indigo-600">{{ $record->severity }}/{{ $record->ailment->severity_scale }} · {{ $record->created_at->localized('day') }}</div>
                    @if ($record->notes)
                        <p class="mt-2 text-sm text-gray-600">{{ $record->notes }}</p>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('Nothing on file. Either you are fine, or you are not admitting it.') }}</li>
            @endforelse
        </ul>
    </div>
</div>

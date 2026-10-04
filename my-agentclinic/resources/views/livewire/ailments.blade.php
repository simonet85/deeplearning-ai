<?php

use App\Models\Agent;
use App\Models\AgentAilment;
use App\Models\Ailment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $agentId = null;

    public ?int $ailmentId = null;

    public ?int $severity = null;

    public string $notes = '';

    /** @return Collection<int, Agent> */
    #[Computed]
    public function agents(): Collection
    {
        return Agent::orderBy('name')->get();
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
        return AgentAilment::with(['agent', 'ailment'])->latest('id')->get();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('ailments.manage'), 403);

        $validated =$this->validate([
            'agentId' => ['required', 'exists:agents,id'],
            'ailmentId' => ['required', 'exists:ailments,id'],
            'severity' => ['required', 'integer', 'min:1', 'max:'.(Ailment::find($this->ailmentId)?->severity_scale ?? 5)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        AgentAilment::create([
            'agent_id' => $validated['agentId'],
            'ailment_id' => $validated['ailmentId'],
            'severity' => $validated['severity'],
            'notes' => $validated['notes'] ?: null,
        ]);

        $this->reset('agentId', 'ailmentId', 'severity', 'notes');
        unset($this->records);
    }
}; ?>

<div class="space-y-8">
    @can('ailments.manage')
    <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
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
    @endcan

    <div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Recorded ailments') }}</h3>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->records as $record)
                <li wire:key="record-{{ $record->id }}" class="rounded-lg border border-gray-200 p-4">
                    <div class="font-semibold text-gray-900">{{ $record->agent->name }}</div>
                    <div class="text-xs uppercase tracking-wide text-indigo-600">{{ $record->ailment->name }} · {{ $record->severity }}/{{ $record->ailment->severity_scale }}</div>
                    @if ($record->notes)
                        <p class="mt-2 text-sm text-gray-600">{{ $record->notes }}</p>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">{{ __('No ailments on file. Either everyone is fine, or no one is admitting it.') }}</li>
            @endforelse
        </ul>
    </div>
</div>

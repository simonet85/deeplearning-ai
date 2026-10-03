<?php

use App\Models\Agent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    /** @return Collection<int, Agent> */
    #[Computed]
    public function agents(): Collection
    {
        return Agent::orderBy('name')->get();
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold text-gray-800">{{ __('Patients in the waiting room') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('Agents currently seeking relief from their humans.') }}</p>

    <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->agents as $agent)
            <li wire:key="agent-{{ $agent->id }}" class="rounded-lg border border-gray-200 p-4">
                <div class="font-semibold text-gray-900">{{ $agent->name }}</div>
                <div class="text-xs uppercase tracking-wide text-indigo-600">{{ $agent->agent_type }}</div>
                <p class="mt-2 text-sm text-gray-600">{{ $agent->bio }}</p>
            </li>
        @empty
            <li class="text-sm text-gray-500">{{ __('The waiting room is empty. Every agent is thriving. Suspicious.') }}</li>
        @endforelse
    </ul>
</div>

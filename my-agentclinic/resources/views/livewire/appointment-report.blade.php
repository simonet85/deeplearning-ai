<?php

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Therapy;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    /** @return Collection<int, Agent> */
    #[Computed]
    public function byAgent(): Collection
    {
        $active = fn ($query) => $query->where('status', '!=', AppointmentStatus::Cancelled);

        return Agent::withCount(['appointments' => $active])
            ->whereHas('appointments', $active)
            ->orderByDesc('appointments_count')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Therapy> */
    #[Computed]
    public function byTherapy(): Collection
    {
        $active = fn ($query) => $query->where('status', '!=', AppointmentStatus::Cancelled);

        return Therapy::withCount(['appointments' => $active])
            ->whereHas('appointments', $active)
            ->orderByDesc('appointments_count')
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold text-gray-800">{{ __('Appointment report') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('Cancelled appointments are not counted.') }}</p>

    <div class="mt-4 grid gap-6 sm:grid-cols-2">
        <div>
            <h4 class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ __('By agent') }}</h4>
            <ul class="mt-2 divide-y divide-gray-100 text-sm">
                @forelse ($this->byAgent as $agent)
                    <li wire:key="report-agent-{{ $agent->id }}" class="flex justify-between py-2">
                        <span class="text-gray-800">{{ $agent->name }}</span>
                        <span class="font-medium text-gray-600">{{ $agent->appointments_count }}</span>
                    </li>
                @empty
                    <li class="py-2 text-gray-500">{{ __('Nothing to report. Suspiciously well-adjusted.') }}</li>
                @endforelse
            </ul>
        </div>

        <div>
            <h4 class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ __('By therapy') }}</h4>
            <ul class="mt-2 divide-y divide-gray-100 text-sm">
                @forelse ($this->byTherapy as $therapy)
                    <li wire:key="report-therapy-{{ $therapy->id }}" class="flex justify-between py-2">
                        <span class="text-gray-800">{{ $therapy->name }}</span>
                        <span class="font-medium text-gray-600">{{ $therapy->appointments_count }}</span>
                    </li>
                @empty
                    <li class="py-2 text-gray-500">{{ __('No therapies prescribed yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

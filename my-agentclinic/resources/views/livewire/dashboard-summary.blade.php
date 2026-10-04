<?php

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    #[Computed]
    public function upcomingCount(): int
    {
        return Appointment::where('status', AppointmentStatus::Booked)->where('datetime', '>=', now())->count();
    }

    #[Computed]
    public function openSlotCount(): int
    {
        return Availability::open()->count();
    }

    #[Computed]
    public function agentCount(): int
    {
        return Agent::count();
    }
}; ?>

<dl class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg border border-gray-200 p-4">
        <dt class="text-xs uppercase tracking-wide text-indigo-600">{{ __('Upcoming sessions') }}</dt>
        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $this->upcomingCount }}</dd>
    </div>
    <div class="rounded-lg border border-gray-200 p-4">
        <dt class="text-xs uppercase tracking-wide text-indigo-600">{{ __('Open slots') }}</dt>
        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $this->openSlotCount }}</dd>
    </div>
    <div class="rounded-lg border border-gray-200 p-4">
        <dt class="text-xs uppercase tracking-wide text-indigo-600">{{ __('Patients') }}</dt>
        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $this->agentCount }}</dd>
    </div>
</dl>

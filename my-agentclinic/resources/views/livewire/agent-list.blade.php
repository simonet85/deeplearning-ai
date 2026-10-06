<?php

use App\Models\Agent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $editingId = null;

    public string $email = '';

    /** @return Collection<int, Agent> */
    #[Computed]
    public function agents(): Collection
    {
        return Agent::with('user')->orderBy('name')->get();
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->check(), 403);

        $agent = Agent::findOrFail($id);

        $this->editingId = $agent->id;
        $this->email = (string) $agent->email;
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(auth()->check(), 403);

        $validated = $this->validate(['email' => ['nullable', 'email', 'max:255']]);

        Agent::findOrFail($this->editingId)->update(['email' => $validated['email'] ?: null]);

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'email');
        $this->resetValidation();
        unset($this->agents);
    }
}; ?>

<div>
    <h3 class="text-lg font-semibold text-gray-800">{{ __('Patients in the waiting room') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('Agents currently seeking relief from their humans.') }}</p>

    <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->agents as $agent)
            <li wire:key="agent-{{ $agent->id }}" class="rounded-lg border border-line bg-surface-raised p-4">
                @php($owner = $agent->user)
                <div class="flex items-center gap-3">
                    <x-avatar :user="$owner" :name="$agent->name" class="h-10 w-10 shrink-0" />
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900">{{ $agent->name }}</div>
                        <div class="text-xs uppercase tracking-[0.08em] text-indigo-600">{{ $agent->agent_type }}</div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">{{ $agent->bio }}</p>

                @auth
                    @if ($editingId === $agent->id)
                        <form wire:submit="save" class="mt-3 space-y-2">
                            <x-input-label for="email-{{ $agent->id }}" :value="__('Email for confirmations')" />
                            <x-text-input id="email-{{ $agent->id }}" type="email" wire:model="email" class="touch-target block w-full" />
                            <x-input-error :messages="$errors->get('email')" />
                            <div class="flex flex-wrap gap-2">
                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                <x-secondary-button type="button" wire:click="cancel">{{ __('Cancel') }}</x-secondary-button>
                            </div>
                        </form>
                    @else
                        <p class="mt-2 text-xs text-gray-500">{{ $agent->email ?: __('No email on file') }}</p>
                        <x-secondary-button type="button" class="mt-2" wire:click="edit({{ $agent->id }})">{{ __('Edit email') }}</x-secondary-button>
                    @endif
                @endauth
            </li>
        @empty
            <li class="text-sm text-gray-500">{{ __('The waiting room is empty. Every agent is thriving. Suspicious.') }}</li>
        @endforelse
    </ul>
</div>

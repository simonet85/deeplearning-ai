<?php

use App\Models\Agent;
use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public string $search = '';

    /** The user whose individual permissions are being edited, if any. */
    public ?int $openId = null;

    /** @var array<int, string> individual permission names ticked for that user */
    public array $extra = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        $term = '%'.$this->search.'%';

        return User::with(['roles', 'permissions', 'agent'])
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term),
            ))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function roleNames(): Collection
    {
        return Role::orderBy('id')->pluck('name');
    }

    public function changeRole(int $id, string $role): void
    {
        $this->authorizeManage();

        $user = User::findOrFail($id);

        if ($user->is(auth()->user())) {
            $this->addError("user.$id", __('You cannot change your own role.'));

            return;
        }

        $newRole = Role::where('name', $role)->where('guard_name', Access::GUARD)->first();

        if (! $newRole) {
            $this->addError("user.$id", __('That role does not exist.'));

            return;
        }

        $user->syncRoles($newRole);
        $this->ensureAgentRecord($user->id);
        $this->refreshUsers();
    }

    public function togglePanel(int $id): void
    {
        $this->authorizeManage();

        if ($this->openId === $id) {
            $this->reset('openId', 'extra');

            return;
        }

        $this->openId = $id;
        $this->extra = User::findOrFail($id)->getDirectPermissions()->pluck('name')->all();
        $this->resetValidation();
    }

    public function saveExtra(): void
    {
        $this->authorizeManage();

        $user = User::findOrFail($this->openId);
        $id = $user->id;

        if ($user->is(auth()->user())) {
            $this->addError("user.$id", __('You cannot change your own permissions.'));

            return;
        }

        $names = array_values(array_intersect(Access::permissionNames(), $this->extra));

        $user->syncPermissions($names);
        $this->ensureAgentRecord($id);
        $this->refreshUsers();
        $this->dispatch('permissions-saved', user: $id);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('users.manage'), 403);
    }

    /** Agent pages show the signed-in user's own agent record, so give one to anyone who can use them. */
    private function ensureAgentRecord(int $id): void
    {
        $user = User::with('agent')->findOrFail($id);

        if ($user->agent === null && $user->hasAnyPermission(['my-ailments.use', 'my-appointments.use'])) {
            Agent::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'agent_type' => 'Agent',
            ]);
        }
    }

    private function refreshUsers(): void
    {
        unset($this->users);
    }
}; ?>

<div class="space-y-6">
    <div class="max-w-md">
        <x-input-label for="search" :value="__('Search users')" />
        <x-text-input id="search" type="search" wire:model.live.debounce.300ms="search" class="touch-target mt-1 block w-full" placeholder="{{ __('Name or e-mail') }}" />
    </div>

    <ul class="space-y-4">
        @forelse ($this->users as $user)
            @php($own = $user->is(auth()->user()))
            @php($currentRole = $user->roles->first())
            <li wire:key="user-{{ $user->id }}" class="rounded-lg border border-line bg-surface-raised p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-avatar :user="$user" class="h-10 w-10 shrink-0" />
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900">{{ $user->name }} @if ($own)<span class="text-xs font-normal text-gray-500">({{ __('you') }})</span>@endif</div>
                            <div class="truncate text-sm text-gray-500">{{ $user->email }}</div>
                        </div>
                        @if ($user->agent)
                            <span class="rounded-full bg-scrub-soft px-2.5 py-0.5 text-xs font-semibold text-scrub">{{ __('Agent record') }}</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="sr-only" for="role-{{ $user->id }}">{{ __('Role') }}</label>
                        <select id="role-{{ $user->id }}" wire:change="changeRole({{ $user->id }}, $event.target.value)" @disabled($own)
                                class="touch-target rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @unless ($currentRole)
                                <option value="" selected>{{ __('No role') }}</option>
                            @endunless
                            @foreach ($this->roleNames as $roleName)
                                <option value="{{ $roleName }}" @selected($currentRole && $currentRole->name === $roleName)>{{ __($roleName) }}</option>
                            @endforeach
                        </select>

                        <x-secondary-button type="button" wire:click="togglePanel({{ $user->id }})">
                            {{ $openId === $user->id ? __('Close') : __('Extra permissions') }}
                            @if ($user->permissions->isNotEmpty())({{ $user->permissions->count() }})@endif
                        </x-secondary-button>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('user.'.$user->id)" class="mt-2" />

                @if ($openId === $user->id)
                    @php($viaRole = $user->getPermissionsViaRoles()->pluck('name')->all())
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-sm text-gray-600">{{ __('Permissions from the role are always on. Tick extra ones to grant this user more than their role gives.') }}</p>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            @foreach (\App\Support\Access::grouped() as $group => $permissions)
                                <fieldset wire:key="user-{{ $user->id }}-{{ $group }}">
                                    <legend class="text-xs font-semibold uppercase tracking-[0.08em] text-indigo-600">{{ __($group) }}</legend>
                                    @foreach ($permissions as $name => $label)
                                        @php($fromRole = in_array($name, $viaRole, true))
                                        <label class="touch-target flex items-center gap-2 text-sm text-gray-700">
                                            @if ($fromRole)
                                                <input type="checkbox" checked disabled class="rounded border-gray-300 text-gray-400 shadow-sm">
                                            @else
                                                <input type="checkbox" wire:model="extra" value="{{ $name }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            @endif
                                            <span>{{ __($label) }} @if ($fromRole)<span class="text-xs text-gray-400">({{ __('from role') }})</span>@endif</span>
                                        </label>
                                    @endforeach
                                </fieldset>
                            @endforeach
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <x-primary-button type="button" wire:click="saveExtra">{{ __('Save permissions') }}</x-primary-button>
                            <x-action-message on="permissions-saved">{{ __('Saved.') }}</x-action-message>
                        </div>
                    </div>
                @endif
            </li>
        @empty
            <li class="text-sm text-gray-500">{{ __('No users match that search.') }}</li>
        @endforelse
    </ul>
</div>

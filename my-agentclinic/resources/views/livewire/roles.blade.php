<?php

use App\Models\User;
use App\Support\Access;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new class extends Component
{
    public string $newName = '';

    public ?int $renamingId = null;

    public string $renameName = '';

    /** @var array<int, array<int, string>> permission names ticked for each role, keyed by role id */
    public array $selected = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        $this->loadSelected();
    }

    /** @return Collection<int, Role> */
    #[Computed]
    public function roles(): Collection
    {
        return Role::withCount('users')->orderBy('id')->get();
    }

    public function createRole(): void
    {
        $this->authorizeManage();

        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/', Rule::unique('roles', 'name')],
        ]);

        Role::create(['name' => $validated['newName'], 'guard_name' => Access::GUARD]);

        $this->reset('newName');
        $this->refreshRoles();
    }

    public function startRenaming(int $id): void
    {
        $this->authorizeManage();

        $role = Role::findOrFail($id);

        if (Access::isBuiltIn($role->name)) {
            $this->addError("role.$id", __('The built-in roles keep their names.'));

            return;
        }

        $this->renamingId = $role->id;
        $this->renameName = $role->name;
        $this->resetValidation();
    }

    public function renameRole(): void
    {
        $this->authorizeManage();

        $role = Role::findOrFail($this->renamingId);
        abort_if(Access::isBuiltIn($role->name), 403);

        $validated = $this->validate([
            'renameName' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->update(['name' => $validated['renameName']]);

        $this->cancelRenaming();
        $this->refreshRoles();
    }

    public function cancelRenaming(): void
    {
        $this->reset('renamingId', 'renameName');
        $this->resetValidation();
    }

    public function savePermissions(int $id): void
    {
        $this->authorizeManage();

        $role = Role::findOrFail($id);
        $names = array_intersect(Access::permissionNames(), $this->selected[$id] ?? []);

        // The administrator role always keeps the permissions that manage access, so nobody can lock everyone out.
        if ($role->name === 'admin') {
            $names = array_intersect(Access::permissionNames(), [...$names, ...Access::ADMIN_LOCKED]);
        }

        DB::beginTransaction();
        $role->syncPermissions(array_values($names));

        // Whatever is ticked, someone must still be able to manage users and roles, or nobody could undo a mistake.
        if (! User::permission('users.manage')->exists() || ! User::permission('roles.manage')->exists()) {
            DB::rollBack();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->loadSelected();
            $this->addError("role.$id", __('That would leave nobody able to manage users and roles.'));

            return;
        }

        DB::commit();

        $this->refreshRoles();
        $this->dispatch('permissions-saved', role: $role->id);
    }

    public function deleteRole(int $id): void
    {
        $this->authorizeManage();

        $role = Role::withCount('users')->findOrFail($id);

        if (Access::isBuiltIn($role->name)) {
            $this->addError("role.$id", __('The built-in roles cannot be deleted.'));

            return;
        }

        if ($role->users_count > 0) {
            $this->addError("role.$id", trans_choice('This role still has :count user. Move them to another role first.|This role still has :count users. Move them to another role first.', $role->users_count));

            return;
        }

        $role->delete();

        if ($this->renamingId === $id) {
            $this->cancelRenaming();
        }

        $this->refreshRoles();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);
    }

    private function loadSelected(): void
    {
        $this->selected = Role::with('permissions')->get()
            ->mapWithKeys(fn (Role $role) => [$role->id => $role->permissions->pluck('name')->all()])
            ->all();
    }

    private function refreshRoles(): void
    {
        unset($this->roles);
        $this->loadSelected();
    }
}; ?>

<div class="space-y-8">
    <form wire:submit="createRole" class="grid gap-4 sm:grid-cols-3">
        <h3 class="text-lg font-semibold text-gray-800 sm:col-span-3">{{ __('Add a role') }}</h3>

        <div class="sm:col-span-2">
            <x-input-label for="newName" :value="__('Role name')" />
            <x-text-input id="newName" type="text" wire:model="newName" class="touch-target mt-1 block w-full" placeholder="{{ __('Reception') }}" />
            <x-input-error :messages="$errors->get('newName')" class="mt-2" />
        </div>

        <div class="flex items-end">
            <x-primary-button>{{ __('Add role') }}</x-primary-button>
        </div>
    </form>

    <div class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-800">{{ __('Roles and their permissions') }}</h3>

        @foreach ($this->roles as $role)
            @php($builtIn = \App\Support\Access::isBuiltIn($role->name))
            <section wire:key="role-{{ $role->id }}" class="rounded-lg border border-gray-200 p-4">
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($renamingId === $role->id)
                            <form wire:submit="renameRole" class="flex flex-wrap items-center gap-2">
                                <x-text-input type="text" wire:model="renameName" aria-label="{{ __('New name') }}" class="touch-target" />
                                <x-primary-button>{{ __('Rename') }}</x-primary-button>
                                <x-secondary-button type="button" wire:click="cancelRenaming">{{ __('Cancel') }}</x-secondary-button>
                            </form>
                        @else
                            <h4 class="text-base font-semibold text-gray-900">{{ $role->name }}</h4>
                        @endif

                        @if ($builtIn)
                            <span class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ __('Built-in') }}</span>
                        @endif
                        <span class="text-xs text-gray-500">{{ trans_choice(':count user|:count users', $role->users_count) }}</span>
                    </div>

                    @unless ($builtIn)
                        <div class="flex flex-wrap gap-2">
                            <x-secondary-button type="button" wire:click="startRenaming({{ $role->id }})">{{ __('Rename') }}</x-secondary-button>
                            <x-danger-button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="{{ __('Delete this role?') }}">{{ __('Delete') }}</x-danger-button>
                        </div>
                    @endunless
                </header>

                <x-input-error :messages="$errors->get('renameName')" class="mt-2" />
                <x-input-error :messages="$errors->get('role.'.$role->id)" class="mt-2" />

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (\App\Support\Access::grouped() as $group => $permissions)
                        <fieldset wire:key="role-{{ $role->id }}-{{ $group }}">
                            <legend class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ __($group) }}</legend>
                            @foreach ($permissions as $name => $label)
                                @php($locked = $role->name === 'admin' && in_array($name, \App\Support\Access::ADMIN_LOCKED, true))
                                <label class="touch-target flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="selected.{{ $role->id }}" value="{{ $name }}" @disabled($locked)
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span>{{ __($label) }} @if ($locked)<span class="text-xs text-gray-400">({{ __('always on') }})</span>@endif</span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button type="button" wire:click="savePermissions({{ $role->id }})">{{ __('Save permissions') }}</x-primary-button>
                    <x-action-message on="permissions-saved">{{ __('Saved.') }}</x-action-message>
                </div>
            </section>
        @endforeach
    </div>
</div>

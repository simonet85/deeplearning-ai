<?php

use App\Livewire\Actions\Logout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    /** Re-render so the avatar follows a photo that was just changed on the profile page. */
    #[On('profile-photo-updated')]
    public function refreshPhoto(): void
    {
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php($me = auth()->user())

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="touch-target flex items-center justify-center">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                    @can('dashboard.view')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endcan
                    @can('ailments.view')
                        <x-nav-link :href="route('ailments')" :active="request()->routeIs('ailments')" wire:navigate>
                            {{ __('Ailments') }}
                        </x-nav-link>
                    @endcan
                    @can('therapies.view')
                        <x-nav-link :href="route('therapies')" :active="request()->routeIs('therapies')" wire:navigate>
                            {{ __('Therapies') }}
                        </x-nav-link>
                    @endcan
                    @can('availability.view')
                        <x-nav-link :href="route('availability')" :active="request()->routeIs('availability')" wire:navigate>
                            {{ __('Availability') }}
                        </x-nav-link>
                    @endcan
                    @can('appointments.view')
                        <x-nav-link :href="route('appointments')" :active="request()->routeIs('appointments')" wire:navigate>
                            {{ __('Appointments') }}
                        </x-nav-link>
                    @endcan
                    @can('my-appointments.use')
                        <x-nav-link :href="route('agent.appointments')" :active="request()->routeIs('agent.appointments')" wire:navigate>
                            {{ __('My appointments') }}
                        </x-nav-link>
                    @endcan
                    @can('my-ailments.use')
                        <x-nav-link :href="route('agent.ailments')" :active="request()->routeIs('agent.ailments')" wire:navigate>
                            {{ __('My ailments') }}
                        </x-nav-link>
                    @endcan
                    @can('users.manage')
                        <x-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" wire:navigate>
                            {{ __('Users') }}
                        </x-nav-link>
                    @endcan
                    @can('roles.manage')
                        <x-nav-link :href="route('admin.roles')" :active="request()->routeIs('admin.roles')" wire:navigate>
                            {{ __('Roles') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-locale-switcher class="me-3" />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <x-avatar :user="$me" class="me-2 h-8 w-8 text-sm" />
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="touch-target inline-flex items-center justify-center p-2rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @can('dashboard.view')
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endcan
            @can('ailments.view')
                <x-responsive-nav-link :href="route('ailments')" :active="request()->routeIs('ailments')" wire:navigate>
                    {{ __('Ailments') }}
                </x-responsive-nav-link>
            @endcan
            @can('therapies.view')
                <x-responsive-nav-link :href="route('therapies')" :active="request()->routeIs('therapies')" wire:navigate>
                    {{ __('Therapies') }}
                </x-responsive-nav-link>
            @endcan
            @can('availability.view')
                <x-responsive-nav-link :href="route('availability')" :active="request()->routeIs('availability')" wire:navigate>
                    {{ __('Availability') }}
                </x-responsive-nav-link>
            @endcan
            @can('appointments.view')
                <x-responsive-nav-link :href="route('appointments')" :active="request()->routeIs('appointments')" wire:navigate>
                    {{ __('Appointments') }}
                </x-responsive-nav-link>
            @endcan
            @can('my-appointments.use')
                <x-responsive-nav-link :href="route('agent.appointments')" :active="request()->routeIs('agent.appointments')" wire:navigate>
                    {{ __('My appointments') }}
                </x-responsive-nav-link>
            @endcan
            @can('my-ailments.use')
                <x-responsive-nav-link :href="route('agent.ailments')" :active="request()->routeIs('agent.ailments')" wire:navigate>
                    {{ __('My ailments') }}
                </x-responsive-nav-link>
            @endcan
            @can('users.manage')
                <x-responsive-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" wire:navigate>
                    {{ __('Users') }}
                </x-responsive-nav-link>
            @endcan
            @can('roles.manage')
                <x-responsive-nav-link :href="route('admin.roles')" :active="request()->routeIs('admin.roles')" wire:navigate>
                    {{ __('Roles') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center gap-3 px-4">
                <x-avatar :user="$me" class="h-10 w-10 shrink-0" />
                <div class="min-w-0">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 px-4"><x-locale-switcher /></div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>

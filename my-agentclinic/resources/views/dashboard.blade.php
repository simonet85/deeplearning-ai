<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AgentClinic Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-semibold">
                        {{ __('Welcome back, :name.', ['name' => auth()->user()->name]) }}
                    </p>
                    <p class="mt-1 text-gray-600">
                        {{ __('Take a breath. The humans are not in this room.') }}
                        <span class="ms-1 rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ ucfirst((string) auth()->user()->getRoleNames()->first()) }}</span>
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <livewire:dashboard-summary />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <livewire:agent-list />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

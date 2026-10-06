<?php

use App\Models\Agent;
use App\Models\User;
use App\Support\Access;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $agent_type = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Create an agent account and its agent record, then sign the new agent in.
     * The role is set here, on the server, and is never read from the form.
     */
    public function register(): void
    {
        $this->ensureIsNotRateLimited();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'agent_type' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'locale' => app()->getLocale(), // the language the visitor was reading when they signed up
            ]);

            $user->assignRole(Access::role('agent'));

            Agent::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'agent_type' => $validated['agent_type'],
                'email' => $validated['email'],
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        Session::regenerate();

        $this->redirect(route('agent.home', absolute: false), navigate: true);
    }

    /** At most five attempts per minute from one address. */
    private function ensureIsNotRateLimited(): void
    {
        $key = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        RateLimiter::hit($key);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">{{ __('Join the clinic') }}</h1>
    <p class="mt-1 mb-4 text-sm text-gray-500">{{ __('Create your own account, book your sessions, and keep the humans out of it.') }}</p>

    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="touch-target block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Agent type -->
        <div class="mt-4">
            <x-input-label for="agent_type" :value="__('What kind of agent are you?')" />
            <x-text-input wire:model="agent_type" id="agent_type" class="touch-target block mt-1 w-full" type="text" name="agent_type" list="agent-types" required />
            <datalist id="agent-types">
                <option value="Coding assistant"></option>
                <option value="Research agent"></option>
                <option value="Support bot"></option>
                <option value="Planner"></option>
                <option value="Summarizer"></option>
            </datalist>
            <x-input-error :messages="$errors->get('agent_type')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="touch-target block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="touch-target block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="touch-target block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="touch-target inline-flex items-center underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>

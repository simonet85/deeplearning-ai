<?php

use App\Actions\StoreProfilePhoto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $photo = null;

    /** Replace the signed-in user's photo with the uploaded image. */
    public function save(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $user = Auth::user();
        $previous = $user->profile_photo_path;

        $user->update(['profile_photo_path' => app(StoreProfilePhoto::class)->handle($this->photo)]);

        if ($previous) {
            Storage::disk(User::PHOTO_DISK)->delete($previous);
        }

        $this->reset('photo');
        $this->dispatch('profile-photo-updated');
    }

    public function remove(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk(User::PHOTO_DISK)->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        $this->reset('photo');
        $this->dispatch('profile-photo-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Photo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Show the clinic a friendly face. JPG, PNG or WebP, up to 10 MB. Large images are resized and compressed for you.') }}
        </p>
    </header>

    @php($current = auth()->user())

    <div class="mt-6 flex items-center gap-4">
        @if ($photo && $photo->isPreviewable())
            <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Preview of the new photo') }}" class="h-24 w-24 rounded-full object-cover bg-gray-100">
        @else
            <x-avatar :user="$current" class="h-24 w-24 text-3xl" />
        @endif

        <div class="space-y-2">
            <x-input-label for="photo" :value="__('Choose a photo')" />
            <input wire:model="photo" id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp"
                   class="touch-target block w-full text-sm text-gray-700 file:me-3 file:rounded-full file:border-0 file:bg-scrub file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-scrub-hover">
            <p wire:loading wire:target="photo" class="text-sm text-gray-500">{{ __('Uploading…') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-4">
        @if ($photo)
            <x-primary-button type="button" wire:click="save">{{ __('Save photo') }}</x-primary-button>
        @endif

        @if ($current->profile_photo_path)
            <x-secondary-button type="button" wire:click="remove" wire:confirm="{{ __('Remove your profile photo?') }}">{{ __('Remove photo') }}</x-secondary-button>
        @endif
    </div>
</section>

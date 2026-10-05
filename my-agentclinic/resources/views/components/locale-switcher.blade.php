{{-- EN | FR. Each link remembers the choice and comes back to this page (see LocaleController). --}}
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm']) }} role="group" aria-label="{{ __('Language') }}">
    @foreach (\App\Support\Locales::supported() as $code)
        <a href="{{ route('locale', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
           @if (app()->getLocale() === $code) aria-current="true" @endif
           class="touch-target inline-flex items-center justify-center rounded px-2 font-medium uppercase {{ app()->getLocale() === $code ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-800' }}">{{ $code }}</a>
    @endforeach
</div>

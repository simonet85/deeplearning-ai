<button {{ $attributes->merge(['type' => 'submit', 'class' => 'touch-target inline-flex items-center justify-center rounded-full border border-transparent bg-scrub px-5 py-2 text-sm font-semibold text-white hover:bg-scrub-hover focus:outline-none']) }}>
    {{ $slot }}
</button>

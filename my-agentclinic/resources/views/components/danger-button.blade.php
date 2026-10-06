<button {{ $attributes->merge(['type' => 'submit', 'class' => 'touch-target inline-flex items-center justify-center rounded-full border border-transparent bg-critical px-5 py-2 text-sm font-semibold text-white hover:opacity-90 focus:outline-none']) }}>
    {{ $slot }}
</button>

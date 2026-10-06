<button {{ $attributes->merge(['type' => 'button', 'class' => 'touch-target inline-flex items-center justify-center rounded-full border border-line-strong bg-surface-raised px-5 py-2 text-sm font-semibold text-ink hover:bg-surface-sunk focus:outline-none']) }}>
    {{ $slot }}
</button>

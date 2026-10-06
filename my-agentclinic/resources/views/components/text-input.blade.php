@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-sm border-line-strong bg-surface-raised text-ink shadow-sm placeholder:text-ink-muted focus:border-scrub focus:ring-scrub']) }}>

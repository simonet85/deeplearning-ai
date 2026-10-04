<div>
    <livewire:layout.navigation />

    @if ($slot->isNotEmpty())
        <header class="site-heading-bar">
            <div class="site-heading-inner">
                {{ $slot }}
            </div>
        </header>
    @endif
</div>

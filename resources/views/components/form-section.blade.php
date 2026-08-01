@props(['title' => null, 'description' => null])

<div {{ $attributes->class(['card']) }}>
    @if ($title)
        <div class="mb-4 border-b border-slate-200/70 pb-4 sm:mb-5">
            <h2 class="section-heading">{{ $title }}</h2>
            @if ($description)
                <p class="section-description">{{ $description }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>

@props(['type' => 'success', 'message' => null])

{{--
    Renders inside the Livewire component's own view - unlike session()
    flash banners (see layouts/app.blade.php's session('status')/
    session('error')), which only show up after a full page navigation.
    A Livewire action is an AJAX round trip that re-renders just the
    component itself, never the surrounding layout, so a flash-based
    banner set during simpan()/etc. would silently never appear until the
    admin happens to navigate elsewhere. Feed this from a plain component
    property (e.g. public ?string $statusMessage) set directly in the
    action method instead.
--}}
@if ($message)
    @php
        $styles = $type === 'error'
            ? ['wrap' => 'border-red-200 bg-red-50', 'icon' => 'text-red-600', 'text' => 'text-red-800', 'close' => 'text-red-600 hover:text-red-800']
            : ['wrap' => 'border-emerald-200 bg-emerald-50', 'icon' => 'text-emerald-600', 'text' => 'text-emerald-800', 'close' => 'text-emerald-600 hover:text-emerald-800'];
        $icon = $type === 'error'
            ? 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z'
            : 'm5 13 4 4L19 7';
    @endphp
    <div
        {{ $attributes->merge(['class' => "app-notice flex items-start gap-3 rounded-2xl border p-4 text-sm shadow-xs ring-1 ring-inset ring-white/40 {$styles['wrap']} {$styles['text']}"]) }}
        x-data="{ show: true }"
        x-show="show"
        x-transition
        role="{{ $type === 'error' ? 'alert' : 'status' }}"
    >
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/60 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4.5 w-4.5 {{ $styles['icon'] }}"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
        </div>
        <p class="flex-1">{{ $message }}</p>
        <button type="button" x-on:click="show = false" class="rounded-lg px-1.5 py-0.5 text-lg leading-none transition hover:bg-white/60 {{ $styles['close'] }}" aria-label="Tutup pemberitahuan">&times;</button>
    </div>
@endif

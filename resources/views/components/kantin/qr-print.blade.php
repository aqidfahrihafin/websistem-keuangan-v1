@props(['unitUsaha'])

@php
    $appSettings = app(\App\Services\AppSettingsService::class);
    // Unique per render so two instances of this component on one page
    // (unlikely today, but cheap to guard) never collide on the SVG
    // <pattern> id.
    $patternId = 'qr-lattice-'.$unitUsaha->id.'-'.uniqid();
@endphp

{{-- QRIS-style payment card: a plain black/white QR (kept untouched for
     scan reliability - all the branding lives in the frame around it, the
     same way real QRIS stickers work) inside a card carrying the pondok's
     own visual identity - the teal gradient + geometric lattice motif used
     for brand "karakter kita" elsewhere (splash/login/home header on the
     mobile app), reproduced here as a tiled SVG pattern since this is
     server-rendered HTML, not the Flutter canvas widget. --}}
<div id="qr-card-{{ $unitUsaha->id }}" class="qr-print-area mx-auto w-full max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
    <div class="relative overflow-hidden bg-linear-to-br from-teal-700 to-slate-900 px-6 pb-10 pt-7 text-center text-white">
        <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-10" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="{{ $patternId }}" width="36" height="36" patternUnits="userSpaceOnUse">
                    <path d="M18 3 21 13 31 10 24 18 31 26 21 23 18 33 15 23 5 26 12 18 5 10 15 13Z" fill="none" stroke="white" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#{{ $patternId }})" />
        </svg>
        <div class="relative">
            {{-- A white chip behind the badge (not a translucent one) so an
                 uploaded logo keeps its own colors/contrast regardless of
                 what's inside it, instead of blending into the teal
                 gradient the way a tinted background would. --}}
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-2 shadow-md ring-1 ring-white/40">
                @if ($appSettings->hasLogo())
                    <img src="{{ $appSettings->logoUrl() }}" alt="Logo" class="h-full w-full rounded-lg object-cover">
                @else
                    <span class="text-lg font-bold text-teal-700">{{ mb_strtoupper(mb_substr($appSettings->namaAplikasi(), 0, 1)) }}</span>
                @endif
            </div>
            {{-- Full nama pondok, never abbreviated - this is the sign that
                 ends up printed and taped to a physical kantin counter, so
                 it needs to read as the pondok's actual name at a glance,
                 not an initialism only staff would recognize. --}}
            <p class="mx-auto mt-3 max-w-64 text-xs font-medium uppercase leading-relaxed tracking-wider text-teal-100/85">{{ $appSettings->namaPondok() }}</p>
            <p class="mt-1 text-lg font-bold tracking-wide">Scan &amp; Bayar</p>
        </div>
    </div>

    <div class="relative -mt-7 px-6">
        {{-- w-72 is the panel's PREFERRED width (240px QR + 24px padding
             either side) - max-w-full lets it shrink on narrower
             viewports/modals instead of forcing the card wider than its
             container (the previous w-fit around a fixed-pixel QR SVG
             couldn't shrink at all, so it overflowed past the card edge on
             anything narrower than ~330px). [&>svg] forces the QR's own raw
             width/height attributes to instead fill this shrinkable box,
             scaling the QR down proportionally right along with it. --}}
        <div class="relative mx-auto w-72 max-w-full rounded-2xl bg-white p-5 shadow-md ring-1 ring-slate-100 [&>svg]:h-auto [&>svg]:w-full">
            <span class="absolute left-2 top-2 h-6 w-6 rounded-tl-lg border-l-2 border-t-2 border-teal-600"></span>
            <span class="absolute right-2 top-2 h-6 w-6 rounded-tr-lg border-r-2 border-t-2 border-teal-600"></span>
            <span class="absolute bottom-2 left-2 h-6 w-6 rounded-bl-lg border-b-2 border-l-2 border-teal-600"></span>
            <span class="absolute bottom-2 right-2 h-6 w-6 rounded-br-lg border-b-2 border-r-2 border-teal-600"></span>
            {!! QrCode::size(240)->format('svg')->generate($unitUsaha->kode) !!}
        </div>
    </div>

    <div class="px-6 pb-6 pt-4 text-center">
        <p class="text-lg font-bold text-slate-900">{{ $unitUsaha->nama }}</p>
        <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $unitUsaha->kode }}</p>
        <div class="mx-auto mt-3 h-px w-16 bg-slate-100"></div>
        <p class="mt-3 text-xs leading-relaxed text-slate-500">
            Scan kode ini lewat menu <span class="font-medium text-teal-700">Scan &amp; Bayar</span> di aplikasi untuk membayar langsung dari saldo santri.
        </p>
    </div>
</div>

{{-- "Print just this element" - hides everything else on the page so a
     kantin owner or admin printing this doesn't get nav chrome/modal
     backdrop in the output, only the card itself. --}}
<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .qr-print-area,
        .qr-print-area * {
            visibility: visible;
        }

        .qr-print-area {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding-top: 40px;
        }
    }
</style>

<div class="card p-5 sm:p-8">
    <div class="mb-6 flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Dokumentasi Developer</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">Flow Fitur Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Pilih proses dari menu flow untuk melihat aktor, validasi, perubahan ledger, audit, dan kondisi gagalnya.</p>
        </div>
        <a href="{{ route('dev.skema-database') }}" wire:navigate class="btn-secondary shrink-0">
            Lihat Skema Database
        </a>
    </div>

    <article
        x-data
        x-init="[...$el.querySelectorAll('h2')].forEach((judul) => {
            judul.id = judul.textContent.trim().toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        })"
        class="dev-flow-content prose prose-slate max-w-none
        prose-headings:scroll-mt-24 prose-headings:font-semibold
        prose-h1:hidden prose-h2:border-b prose-h2:border-slate-200 prose-h2:pb-2
        prose-a:text-teal-700 prose-a:no-underline hover:prose-a:underline
        prose-table:block prose-table:overflow-x-auto
        prose-code:before:content-none prose-code:after:content-none
        prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5
        prose-pre:overflow-x-auto prose-pre:rounded-xl prose-pre:bg-slate-950"
    >
        {!! $konten !!}
    </article>
</div>

@push('styles')
    <style>
        /*
         * `prose-code` dipakai untuk kode pendek di dalam paragraf. Aturan
         * tersebut tidak boleh memberi latar putih pada `<code>` yang berada
         * di dalam diagram `<pre>`, karena teks diagram menjadi tidak terbaca.
         */
        .dev-flow-content pre {
            overflow-x: auto;
            border: 1px solid rgb(51 65 85);
            border-radius: 0.75rem;
            background: rgb(15 23 42) !important;
            color: rgb(241 245 249) !important;
        }

        .dev-flow-content pre code {
            display: block;
            padding: 0;
            border: 0;
            background: transparent !important;
            color: inherit !important;
            font-weight: 400;
            white-space: pre;
        }
    </style>
@endpush

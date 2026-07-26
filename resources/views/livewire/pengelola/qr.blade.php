<div class="card p-6">
    <x-warning-banner variant="info" title="Petunjuk penggunaan QR" class="mb-5">
        Cetak dan tempel kode ini di meja kasir. Wali membayar dengan memindai kode melalui menu <strong>Scan &amp; Bayar</strong> di aplikasi.
    </x-warning-banner>
    <x-kantin.qr-print :unit-usaha="$unitUsaha" />
    <div class="toolbar mt-6 justify-center sm:justify-center">
        <button
            type="button"
            onclick="downloadElementAsImage(document.getElementById('qr-card-{{ $unitUsaha->id }}'), 'qr-{{ $unitUsaha->kode }}.png')"
            class="btn-secondary"
        >Unduh Gambar</button>
        <button type="button" onclick="window.print()" class="btn-primary">Cetak</button>
    </div>
</div>

<div class="space-y-6">
    <div>
        <h2 class="section-heading">Ringkasan pondok</h2>
        <p class="section-description">Pantauan singkat kondisi santri dan aktivitas keuangan hari ini.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Santri aktif" :value="number_format($totalSantriAktif)" hint="Santri yang tercatat aktif saat ini." tone="sky" icon="users" />
        <x-stat-card label="Total saldo santri" :value="'Rp '.number_format($totalSaldo, 0, ',', '.')" hint="Akumulasi saldo seluruh santri aktif." tone="teal" icon="wallet" />
        <x-stat-card label="Tagihan belum lunas" :value="number_format($tagihanBelumLunas)" hint="Tagihan yang masih perlu diselesaikan." tone="amber" icon="receipt" />
        <x-stat-card label="Transaksi hari ini" :value="number_format($transaksiHariIni)" hint="Jumlah aktivitas transaksi sejak pukul 00.00." tone="emerald" icon="activity" />
    </div>
</div>

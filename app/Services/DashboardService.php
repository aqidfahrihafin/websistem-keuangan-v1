<?php

namespace App\Services;

use App\Models\PenarikanRequest;
use App\Models\SaldoSantri;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const HARI_TREN = 30;

    /**
     * @return array{
     *     santri_aktif: int, santri_baru: int, saldo_santri_total: int,
     *     tagihan_belum_lunas: int, penarikan_menunggu: int, penarikan_disetujui: int,
     *     surat_menunggu_review: int, saldo_kas_pondok: int,
     *     tren_transaksi: array<int, array{tanggal: string, total: int, jumlah: int}>,
     *     tren_kas_pondok: array<int, array{tanggal: string, saldo: int}>,
     *     status_tagihan: array<string, int>, status_santri: array<string, int>,
     *     aktivitas_terbaru: Collection<int, Transaksi>,
     * }
     */
    public function ringkasan(): array
    {
        // Dashboard contains several aggregate and ledger queries. A short
        // shared cache prevents every Livewire render (including typing in
        // its search field) from recalculating the same global numbers.
        // Twenty seconds keeps operational figures sufficiently fresh while
        // allowing many concurrent admins to reuse one calculation. Keep
        // Eloquent models outside this cache: file-based cache may restore a
        // serialized Collection before its class has been autoloaded.
        $ringkasan = Cache::remember(
            'dashboard:ringkasan:v3',
            now()->addSeconds(20),
            fn () => $this->hitungRingkasan(),
        );

        $ringkasan['aktivitas_terbaru'] = Transaksi::with('santri:id,nama')
            ->latest()
            ->limit(8)
            ->get();

        return $ringkasan;
    }

    private function hitungRingkasan(): array
    {
        $mulai = now()->subDays(self::HARI_TREN - 1)->startOfDay();
        $sekarang = now();

        // One LegerKasPondokService call covers both the "current kas pondok
        // balance" headline stat (its saldo_akhir, since entri here already
        // spans everything up to $sekarang) and the 30-day trend chart -
        // no need for a second, separate all-time query.
        $leger = app(LegerKasPondokService::class)->generate($mulai, $sekarang);

        return [
            'santri_aktif' => Santri::where('status', Santri::STATUS_AKTIF)->count(),
            'santri_baru' => Santri::where('status', Santri::STATUS_BARU)->count(),
            'saldo_santri_total' => (int) SaldoSantri::sum('saldo'),
            'tagihan_belum_lunas' => Tagihan::whereIn('status', [Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN])->count(),
            'penarikan_menunggu' => PenarikanRequest::where('status', PenarikanRequest::STATUS_MENUNGGU)->count(),
            'penarikan_disetujui' => PenarikanRequest::where('status', PenarikanRequest::STATUS_DISETUJUI)->count(),
            'surat_menunggu_review' => PenarikanRequest::where('wajib_surat_keterangan', true)
                ->where('surat_keterangan_status', PenarikanRequest::SURAT_MENUNGGU_REVIEW)
                ->count(),
            'saldo_kas_pondok' => $leger['saldo_akhir'],
            'tren_transaksi' => $this->trenTransaksi($mulai, $sekarang),
            'tren_kas_pondok' => $this->trenKasPondok($mulai, $sekarang, $leger),
            'status_tagihan' => $this->statusBreakdown(Tagihan::class, [
                Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN, Tagihan::STATUS_LUNAS, Tagihan::STATUS_DIBATALKAN,
            ]),
            'status_santri' => $this->statusBreakdown(Santri::class, [
                Santri::STATUS_AKTIF, Santri::STATUS_BARU, Santri::STATUS_NONAKTIF, Santri::STATUS_LULUS, Santri::STATUS_KELUAR,
            ]),
        ];
    }

    /**
     * @return array<int, array{tanggal: string, total: int, jumlah: int}>
     */
    private function trenTransaksi(Carbon $mulai, Carbon $sekarang): array
    {
        $rows = Transaksi::query()
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->where('created_at', '>=', $mulai)
            ->selectRaw('DATE(created_at) as tanggal, SUM(nominal) as total, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $hasil = [];

        foreach (CarbonPeriod::create($mulai, $sekarang) as $tanggal) {
            $key = $tanggal->toDateString();
            $row = $rows->get($key);
            $hasil[] = [
                'tanggal' => $key,
                'total' => (int) ($row->total ?? 0),
                'jumlah' => (int) ($row->jumlah ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Forward-fills the running balance across days with no cash movement -
     * a day without an entry keeps yesterday's closing balance, it doesn't
     * drop to 0.
     *
     * @return array<int, array{tanggal: string, saldo: int}>
     */
    private function trenKasPondok(Carbon $mulai, Carbon $sekarang, array $leger): array
    {
        $saldoAkhirHari = [];

        foreach ($leger['entri'] as $entri) {
            // entri is sorted ascending, so the last write per date wins -
            // exactly the end-of-day balance we want.
            $saldoAkhirHari[$entri['tanggal']->toDateString()] = $entri['saldo_berjalan'];
        }

        $hasil = [];
        $berjalan = $leger['saldo_awal'];

        foreach (CarbonPeriod::create($mulai, $sekarang) as $tanggal) {
            $key = $tanggal->toDateString();

            if (array_key_exists($key, $saldoAkhirHari)) {
                $berjalan = $saldoAkhirHari[$key];
            }

            $hasil[] = ['tanggal' => $key, 'saldo' => $berjalan];
        }

        return $hasil;
    }

    /**
     * @param  class-string  $model
     * @param  array<int, string>  $statuses
     * @return array<string, int>
     */
    private function statusBreakdown(string $model, array $statuses): array
    {
        $counts = $model::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return collect($statuses)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
    }
}

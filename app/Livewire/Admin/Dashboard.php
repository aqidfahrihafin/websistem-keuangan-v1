<?php

namespace App\Livewire\Admin;

use App\Models\Transaksi;
use App\Services\AppSettingsService;
use App\Services\DashboardService;
use App\Services\LaporanKeuanganService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    public string $aktivitasSearch = '';

    public function render(DashboardService $service, LaporanKeuanganService $laporanKeuanganService, AppSettingsService $appSettings)
    {
        $ringkasan = $service->ringkasan();
        $jenisLabel = $laporanKeuanganService->semuaJenisLabel();
        $aktivitasSearch = mb_strtolower(trim($this->aktivitasSearch));

        if ($aktivitasSearch !== '') {
            $jenisCocok = collect($jenisLabel)
                ->filter(fn (string $label, string $jenis) => str_contains(mb_strtolower($label), $aktivitasSearch)
                    || str_contains(mb_strtolower($jenis), $aktivitasSearch))
                ->keys()
                ->all();

            $ringkasan['aktivitas_terbaru'] = Transaksi::query()
                ->with('santri:id,nama')
                ->where(function ($query) use ($aktivitasSearch, $jenisCocok) {
                    $query->whereHas('santri', fn ($query) => $query->where(fn ($query) => $query
                        ->where('nama', 'like', "%{$aktivitasSearch}%")
                        ->orWhere('nis', 'like', "%{$aktivitasSearch}%")));

                    if ($jenisCocok !== []) {
                        $query->orWhereIn('jenis', $jenisCocok);
                    }
                })
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('livewire.admin.dashboard', [
            'title' => 'Dashboard',
            // The full map (not a hand-picked subset) - a jenis added later
            // (e.g. pembayaran_kantin, transfer_antar_santri) automatically
            // gets a real label here instead of falling back to its raw
            // snake_case value.
            'jenis_label' => $jenisLabel,
            'nama_aplikasi' => $appSettings->namaAplikasi(),
            'nama_pondok' => $appSettings->namaPondok(),
            'logo_url' => $appSettings->hasLogo() ? $appSettings->logoUrl() : null,
            ...$ringkasan,
        ]);
    }
}

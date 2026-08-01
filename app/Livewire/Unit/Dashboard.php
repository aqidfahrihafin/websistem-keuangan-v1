<?php

namespace App\Livewire\Unit;

use App\Models\Kamar;
use App\Models\Santri;
use App\Services\UnitAccessService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    public function render(UnitAccessService $akses)
    {
        $user = auth()->user();
        $isRayon = $user->hasRole('admin_rayon');
        $unit = $isRayon ? $user->rayonsDikelola()->get() : $user->lembagasDikelola()->get();
        $query = $akses->scopeSantri(Santri::query(), $user);
        $aktif = (clone $query)->where('status', Santri::STATUS_AKTIF);
        $jumlahSantri = (clone $aktif)->count();

        $distribusi = (clone $aktif)
            ->with($isRayon ? 'lembaga:id,nama' : 'rayon:id,nama')
            ->get()
            ->groupBy(fn (Santri $santri) => $isRayon
                ? ($santri->lembaga?->nama ?? 'Belum ada lembaga')
                : ($santri->rayon?->nama ?? 'Belum ada rayon'))
            ->map->count()
            ->sortDesc();

        $kamars = $isRayon
            ? Kamar::query()->whereIn('rayon_id', $unit->pluck('id'))
                ->with('rayon:id,nama')
                ->withCount(['santris as penghuni_aktif_count' => fn ($q) => $q->where('status', Santri::STATUS_AKTIF)])
                ->orderBy('nama')->get()
            : collect();

        $kapasitas = $kamars->sum(fn (Kamar $kamar) => $kamar->kapasitas ?? 0);
        $penghuni = $kamars->sum('penghuni_aktif_count');

        return view('livewire.unit.dashboard', [
            'title' => $isRayon ? 'Dashboard Rayon' : 'Dashboard Lembaga',
            'jenisUnit' => $isRayon ? 'Rayon Pesantren' : 'Lembaga Pendidikan',
            'namaUnit' => $unit->pluck('nama')->join(', ') ?: 'Belum ada unit tertaut',
            'kodeUnit' => $unit->pluck('kode')->join(' · '),
            'isRayon' => $isRayon,
            'jumlahUnit' => $unit->count(),
            'jumlahSantri' => $jumlahSantri,
            'jumlahPutra' => (clone $aktif)->where('jenis_kelamin', 'L')->count(),
            'jumlahPutri' => (clone $aktif)->where('jenis_kelamin', 'P')->count(),
            'belumDitempatkan' => (clone $aktif)->whereNull($isRayon ? 'kamar_id' : 'rayon_id')->count(),
            'jumlahKamar' => $kamars->count(),
            'kapasitas' => $kapasitas,
            'penghuni' => $penghuni,
            'persentaseKapasitas' => $kapasitas > 0 ? min(100, (int) round($penghuni / $kapasitas * 100)) : 0,
            'distribusi' => $distribusi,
            'nilaiDistribusiTerbesar' => max(1, (int) ($distribusi->max() ?? 1)),
            'kamars' => $kamars,
            'santris' => (clone $query)->with(['lembaga:id,nama', 'rayon:id,nama', 'kamar:id,nama'])
                ->latest('updated_at')->limit(6)->get(),
        ]);
    }
}

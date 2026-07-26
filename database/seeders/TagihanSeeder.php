<?php

namespace Database\Seeders;

use App\Models\JenisTagihan;
use App\Models\Periode;
use App\Models\Santri;
use App\Models\TagihanPembayaran;
use App\Models\User;
use App\Services\TagihanService;
use Illuminate\Database\Seeder;

class TagihanSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TagihanService::class);
        $admin = User::where('email', 'admin@pesantren.test')->first();

        $periodes = Periode::orderBy('label')->pluck('label');
        $periodeAktif = Periode::where('is_active', true)->value('label') ?? now()->format('Y-m');

        // Generate tagihan across every seeded periode (not just the active
        // one) so the periode filter/export has real historical data to
        // demonstrate, not just a single month.
        foreach ($periodes as $periode) {
            foreach (JenisTagihan::where('periode', JenisTagihan::PERIODE_BULANAN)->get() as $jenis) {
                $service->generateTagihanForPeriode($jenis, $periode, now()->addDays(10), $admin);
            }
        }

        // Mark the SPP tagihan for the demo santri (NIS 1001000001) as paid
        // tunai for the active periode, so the santri portal has a "lunas" example.
        $spp = JenisTagihan::where('kode', 'SPP-BULANAN')->first();
        $santri = Santri::where('nis', '1001000001')->first();

        if ($spp && $santri) {
            $tagihan = $santri->tagihans()->where('jenis_tagihan_id', $spp->id)->where('periode_label', $periodeAktif)->first();

            if ($tagihan) {
                $service->applyPembayaran($tagihan, $tagihan->nominal, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG, [
                    'dicatat_oleh' => $admin?->id,
                ]);
            }
        }
    }
}

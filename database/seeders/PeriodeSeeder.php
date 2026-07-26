<?php

namespace Database\Seeders;

use App\Models\Periode;
use Illuminate\Database\Seeder;

class PeriodeSeeder extends Seeder
{
    /**
     * A short, believable periode history (two months back through the
     * current month) so the periode filter/list has more than one row to
     * demo, with the current month set as the active periode.
     */
    public function run(): void
    {
        foreach ([2, 1, 0] as $monthsAgo) {
            $bulan = now()->subMonths($monthsAgo);

            Periode::firstOrCreate(
                ['label' => $bulan->format('Y-m')],
                [
                    'tanggal_mulai' => $bulan->copy()->startOfMonth(),
                    'tanggal_selesai' => $bulan->copy()->endOfMonth(),
                    'is_active' => false,
                ]
            );
        }

        Periode::where('label', now()->format('Y-m'))->first()?->activate();
    }
}

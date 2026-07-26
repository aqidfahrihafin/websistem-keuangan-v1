<?php

namespace App\Console\Commands;

use App\Models\Tagihan;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class RemindTagihanJatuhTempo extends Command
{
    protected $signature = 'tagihan:ingatkan-jatuh-tempo';

    protected $description = 'Kirim pengingat push notification untuk tagihan yang akan jatuh tempo 3 hari lagi';

    private const HARI_SEBELUM_JATUH_TEMPO = 3;

    public function handle(PushNotificationService $push): int
    {
        $tanggal = now()->addDays(self::HARI_SEBELUM_JATUH_TEMPO)->toDateString();
        $count = 0;

        Tagihan::query()
            ->whereDate('jatuh_tempo', $tanggal)
            ->whereNull('reminder_terkirim_at')
            ->where('status', '!=', Tagihan::STATUS_LUNAS)
            ->where('status', '!=', Tagihan::STATUS_DIBATALKAN)
            ->with(['santri.walis', 'jenisTagihan'])
            ->chunkById(200, function ($tagihans) use ($push, &$count) {
                foreach ($tagihans as $tagihan) {
                    $santri = $tagihan->santri;
                    $body = "Tagihan {$tagihan->jenisTagihan->nama} {$santri->nama} jatuh tempo {$tagihan->jatuh_tempo->translatedFormat('d F Y')}.";

                    foreach ($santri->walis as $wali) {
                        $push->notify($wali, 'Tagihan Segera Jatuh Tempo', $body, [
                            'type' => 'tagihan_jatuh_tempo',
                            'santri_id' => $santri->id,
                            'tagihan_id' => $tagihan->id,
                        ]);
                    }

                    $tagihan->update(['reminder_terkirim_at' => now()]);
                    $count++;
                }
            });

        $this->info("{$count} pengingat tagihan jatuh tempo dikirim.");

        return self::SUCCESS;
    }
}

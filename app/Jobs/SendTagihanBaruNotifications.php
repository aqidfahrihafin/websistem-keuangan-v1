<?php

namespace App\Jobs;

use App\Models\Tagihan;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued (not inline) - mass tagihan generation can mean hundreds of santri
 * in one admin action, and a synchronous notify loop would hang that
 * request. Re-queries by generated_batch_id rather than being handed the
 * created models directly, since TagihanService::generateTagihanForPeriode()
 * only returns counts.
 */
class SendTagihanBaruNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private string $batchId) {}

    public function handle(PushNotificationService $push): void
    {
        Tagihan::query()
            ->where('generated_batch_id', $this->batchId)
            ->with(['santri.walis', 'jenisTagihan'])
            ->chunkById(200, function ($tagihans) use ($push) {
                foreach ($tagihans as $tagihan) {
                    $santri = $tagihan->santri;
                    $body = "{$santri->nama} memiliki tagihan baru: {$tagihan->jenisTagihan->nama} Rp"
                        .number_format($tagihan->nominal, 0, ',', '.').'.';

                    foreach ($santri->walis as $wali) {
                        $push->notify($wali, 'Tagihan Baru', $body, [
                            'type' => 'tagihan_baru',
                            'santri_id' => $santri->id,
                            'santri_nama' => $santri->nama,
                            'tagihan_id' => $tagihan->id,
                        ]);
                    }
                }
            });
    }
}

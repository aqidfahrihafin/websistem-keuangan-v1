<?php

namespace App\Console\Commands;

use App\Models\Keluarga;
use App\Services\KeluargaLinkingService;
use Illuminate\Console\Command;

/**
 * Rebuilds wali_santris (the wali<->santri link table) from scratch for
 * every keluarga, by no_kk. Normally this table stays in sync on its own -
 * KeluargaLinkingService runs automatically whenever a Santri or User row
 * is created/updated (see SantriObserver/UserObserver) - but a link only
 * ever gets (re)computed by *those* events firing. A raw delete against
 * wali_santris itself (e.g. during a manual data reset that doesn't touch
 * Eloquent) leaves users/santris/keluargas intact but silently drops every
 * link between them, with nothing to trigger a resync. Safe to run any
 * time: additive for missing links, and only removes auto-generated links
 * whose santri no longer actually shares that no_kk.
 */
class SyncWaliLinks extends Command
{
    protected $signature = 'wali:sync-links';

    protected $description = 'Sinkronkan ulang tautan wali-santri (wali_santris) berdasarkan No. KK';

    public function handle(KeluargaLinkingService $linking): int
    {
        $noKks = Keluarga::pluck('no_kk');

        $bar = $this->output->createProgressBar($noKks->count());
        $bar->start();

        foreach ($noKks as $noKk) {
            $linking->syncForNoKk($noKk);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Tautan wali-santri disinkronkan ulang untuk {$noKks->count()} keluarga.");

        return self::SUCCESS;
    }
}

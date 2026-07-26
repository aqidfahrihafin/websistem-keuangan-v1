<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data-only migration, no schema change: encrypts verifikasi_fingerprint_ref
     * at rest. Unlike kartu_santris.uid_kartu/fingerprint_template_ref, this
     * column is write-only (confirmed via a full-repo search - nothing ever
     * queries it back with a WHERE), so it needs no companion hash/blind-index
     * column, just the encrypted cast (see app/Models/PenarikanRequest.php).
     */
    public function up(): void
    {
        DB::table('penarikan_requests')
            ->select('id', 'verifikasi_fingerprint_ref')
            ->whereNotNull('verifikasi_fingerprint_ref')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('penarikan_requests')
                        ->where('id', $row->id)
                        ->update(['verifikasi_fingerprint_ref' => Crypt::encryptString($row->verifikasi_fingerprint_ref)]);
                }
            });
    }

    /**
     * Deliberately does not decrypt-and-restore - see the up() docblock and
     * the sibling kartu_santris migration for the same reasoning. No schema
     * changed here, so there's nothing to structurally reverse either way.
     */
    public function down(): void {}
};

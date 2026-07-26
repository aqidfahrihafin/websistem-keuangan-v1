<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypts uid_kartu/fingerprint_template_ref at rest (RFID card UID and
     * fingerprint match reference - see KartuSantri::hashReference() and
     * app/Services/TrustedDeviceFingerprintVerifier.php). Both are looked up
     * via a plain WHERE elsewhere in the app (Kios\CekSaldo::scan(),
     * TrustedDeviceFingerprintVerifier::verify()), and Eloquent's
     * `encrypted` cast is non-deterministic (random IV per value), so a
     * plain WHERE can never match ciphertext to a plaintext search term.
     * The *_hash columns are a deterministic HMAC-SHA256 (keyed with
     * APP_KEY) "blind index" that those lookups run against instead - the
     * real value stays encrypted, only a non-reversible fingerprint of it
     * is ever compared directly.
     *
     * Uses the query builder throughout (not the Eloquent model) so this
     * backfill is correct regardless of whether app/Models/KartuSantri.php
     * has already been deployed with the new `encrypted` cast by the time
     * this runs - Crypt::encryptString() is exactly what that cast calls
     * internally, so the result is identical either way.
     */
    public function up(): void
    {
        Schema::table('kartu_santris', function (Blueprint $table) {
            $table->string('uid_kartu_hash')->nullable()->after('uid_kartu');
            $table->string('fingerprint_template_ref_hash')->nullable()->after('fingerprint_template_ref');
        });

        DB::table('kartu_santris')
            ->select('id', 'uid_kartu', 'fingerprint_template_ref')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $update = [];

                    if ($row->uid_kartu !== null) {
                        $update['uid_kartu_hash'] = hash_hmac('sha256', $row->uid_kartu, config('app.key'));
                        $update['uid_kartu'] = Crypt::encryptString($row->uid_kartu);
                    }

                    if ($row->fingerprint_template_ref !== null) {
                        $update['fingerprint_template_ref_hash'] = hash_hmac('sha256', $row->fingerprint_template_ref, config('app.key'));
                        $update['fingerprint_template_ref'] = Crypt::encryptString($row->fingerprint_template_ref);
                    }

                    if ($update !== []) {
                        DB::table('kartu_santris')->where('id', $row->id)->update($update);
                    }
                }
            });

        // The old unique() on uid_kartu itself is now meaningless: once it
        // holds ciphertext, two different real-world UIDs (or the same UID
        // encrypted twice) never collide anyway, so it can no longer catch
        // an actual duplicate card. uid_kartu_hash (deterministic) is the
        // only place uniqueness can still be enforced correctly.
        Schema::table('kartu_santris', function (Blueprint $table) {
            $table->dropUnique(['uid_kartu']);
            $table->unique('uid_kartu_hash');
            $table->index('fingerprint_template_ref_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Deliberately does not attempt to decrypt-and-restore plaintext -
        // this is a one-time, effectively irreversible data migration by
        // design (see the class docblock). Rolling back only removes the
        // hash columns and the constraint move; uid_kartu/
        // fingerprint_template_ref remain encrypted.
        Schema::table('kartu_santris', function (Blueprint $table) {
            $table->dropUnique(['uid_kartu_hash']);
            $table->dropIndex(['fingerprint_template_ref_hash']);
            $table->dropColumn(['uid_kartu_hash', 'fingerprint_template_ref_hash']);
            $table->unique('uid_kartu');
        });
    }
};

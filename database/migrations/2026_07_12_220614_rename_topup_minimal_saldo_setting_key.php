<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The setting used to gate how much of a top-up got reserved before the
     * rest was swept into tagihan - now that top-ups are never swept, the
     * same floor instead gates paying a tagihan *from saldo*. Renaming the
     * key (not the encrypted value) preserves whatever an admin already
     * configured instead of silently resetting it to the default.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'topup_minimal_saldo_setelah_topup')
            ->update(['key' => 'tagihan_minimal_saldo_setelah_bayar']);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'tagihan_minimal_saldo_setelah_bayar')
            ->update(['key' => 'topup_minimal_saldo_setelah_topup']);
    }
};

<?php

namespace App\Services;

use App\Models\Kwitansi;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use Illuminate\Support\Str;

/**
 * Issues an official numbered receipt (kwitansi resmi) - distinct from the
 * existing informal "struk" (InvoiceService), whose reference number is
 * just derived fresh from the record's own id every time it's rendered,
 * never persisted or guaranteed stable. A Kwitansi row is created exactly
 * once, at the moment a tagihan or kantin payment succeeds, and its number
 * never changes after that.
 */
class KwitansiService
{
    /**
     * Called from inside TagihanService::applyPembayaran()'s own
     * transaction - covers every payment source (saldo, tunai_langsung,
     * transfer_wali_tagihan), since they all funnel through that one method.
     */
    public function terbitkanUntukTagihan(TagihanPembayaran $pembayaran): Kwitansi
    {
        return $this->terbitkan([
            'jenis' => Kwitansi::JENIS_TAGIHAN,
            'santri_id' => $pembayaran->tagihan->santri_id,
            'nominal' => $pembayaran->nominal,
            'tagihan_pembayaran_id' => $pembayaran->id,
            'transaksi_id' => $pembayaran->transaksi_id,
        ]);
    }

    /**
     * Called from inside KantinPembayaranService::bayar()'s own transaction,
     * right after the santri's side of the debit is recorded.
     */
    public function terbitkanUntukKantin(Transaksi $transaksi): Kwitansi
    {
        return $this->terbitkan([
            'jenis' => Kwitansi::JENIS_KANTIN,
            'santri_id' => $transaksi->santri_id,
            'nominal' => $transaksi->nominal,
            'transaksi_id' => $transaksi->id,
        ]);
    }

    public function terbitkanUntukTopup(TopupWali $topup, Transaksi $transaksi): Kwitansi
    {
        return $this->terbitkan([
            'jenis' => Kwitansi::JENIS_TOPUP,
            'santri_id' => $topup->santri_id,
            'nominal' => $topup->nominal_diminta,
            'transaksi_id' => $transaksi->id,
            'topup_wali_id' => $topup->id,
        ]);
    }

    private function terbitkan(array $data): Kwitansi
    {
        // nomor_kwitansi is NOT NULL + unique, but the real value can only
        // be formatted once the row's own id exists - a random UUID is
        // inserted as a collision-proof placeholder to satisfy both
        // constraints for the brief moment before the second write below
        // overwrites it with the real number. Riding on the database's own
        // auto-increment guarantee for the *real* number is what keeps that
        // second write collision-free under concurrent payments, unlike a
        // naive MAX(id)+1 computed ahead of insert (see
        // KartuSantri::nomorKartuBerikutnya(), which gets away with that
        // only because card issuance is a low-concurrency admin action, not
        // something firing on every payment).
        $kwitansi = Kwitansi::create(array_merge($data, [
            'nomor_kwitansi' => (string) Str::uuid(),
        ]));

        $kwitansi->update([
            'nomor_kwitansi' => sprintf('KWT-%s-%06d', now()->format('Y'), $kwitansi->id),
        ]);

        return $kwitansi;
    }
}

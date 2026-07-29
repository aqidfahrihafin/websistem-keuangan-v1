<?php

namespace App\Http\Resources;

use App\Models\Santri;
use App\Models\UnitUsaha;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'jenis' => $this->jenis,
            'arah' => $this->arah,
            'nominal' => (int) $this->nominal,
            'saldo_sebelum' => (int) $this->saldo_sebelum,
            'saldo_sesudah' => (int) $this->saldo_sesudah,
            'status' => $this->status,
            // Legacy rows may predate the metode field. Older mobile builds
            // require a string here, so keep them readable as system entries.
            'metode' => $this->metode ?? 'sistem',
            // The specific payment channel (bni_va/bca_va/bri_va/qris) for a
            // Midtrans-driven transaksi - the mobile receipt shows this
            // instead of the coarse 'metode' ("Midtrans") when available,
            // same kode the app's own top-up method picker already uses.
            'metode_detail' => $this->metadata['metode_detail'] ?? null,
            // Present only for a topup_transfer_wali row - the Midtrans fee
            // recorded on the TopupWali that generated it (see
            // TopupWaliService::settle()/settleTagihanScoped()), and whether
            // the wali paid it on top or the pondok absorbed it. Null for
            // every other jenis, and for an older topup_transfer_wali row
            // recorded before this was tracked.
            'biaya_midtrans' => isset($this->metadata['biaya_midtrans'])
                ? (int) $this->metadata['biaya_midtrans']
                : null,
            'biaya_ditanggung_wali' => isset($this->metadata['biaya_ditanggung_wali'])
                ? (bool) $this->metadata['biaya_ditanggung_wali']
                : null,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
            // Present only once this transaksi's kwitansi resmi has been
            // issued (pembayaran_tagihan from saldo, and pembayaran_kantin -
            // see KwitansiService) - the mobile app uses this id to fetch a
            // signed PDF link via GET /wali/kwitansi/{id}.
            'kwitansi_id' => $this->kwitansi?->id,
            // Lets a pembayaran_tagihan row that's part of an in-progress
            // cicilan show "terbayar X, sisa Y" without a second API call -
            // null for every transaksi not linked to a tagihan at all.
            'tagihan' => $this->tagihan ? [
                'id' => $this->tagihan->id,
                'jenis_tagihan_nama' => $this->tagihan->jenisTagihan?->nama,
                'periode_label' => $this->tagihan->periode_label,
                'nominal' => (int) $this->tagihan->nominal,
                'nominal_terbayar' => (int) $this->tagihan->nominal_terbayar,
                'sisa' => (int) $this->tagihan->sisa(),
                'status' => $this->tagihan->status,
            ] : null,
            // The counterparty on the other side of this transaksi - who a
            // kantin payment went to, or which santri a transfer_antar_
            // santri moved money to/from (see referensi() on the model).
            // Only Santri/UnitUsaha are surfaced here; penarikan_tunai's
            // PenarikanRequest referensi doesn't add anything a mobile
            // detail view needs beyond `jenis` itself.
            'referensi' => match ($this->referensi_type) {
                Santri::class => $this->referensi ? [
                    'type' => 'santri',
                    'nama' => $this->referensi->nama,
                    'nis' => $this->referensi->nis,
                ] : null,
                UnitUsaha::class => $this->referensi ? [
                    'type' => 'unit_usaha',
                    'nama' => $this->referensi->nama,
                    'kode' => $this->referensi->kode,
                ] : null,
                default => null,
            },
        ];
    }
}

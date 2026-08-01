<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopupWaliResource extends JsonResource
{
    /**
     * TopupController's store()/storeCore()/show()/sync() all return this
     * resource as the response root (not nested inside another array like
     * TagihanController::bayar() does), so Laravel's default 'data' envelope
     * would apply here - but the documented API contract (/dev/api/wali) and
     * the mobile client (WaliApi/Topup.fromJson) both expect the fields at
     * the top level. Disabling the wrap keeps this resource consistent with
     * every other single-object response in this API.
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'santri_id' => $this->santri_id,
            'tagihan_id' => $this->tagihan_id,
            'tujuan' => $this->tujuan,
            'transaksi_tabungan_id' => $this->transaksi_tabungan_id,
            'nominal_diminta' => $this->nominal_diminta,
            'status' => $this->status,
            'nominal_potongan_tagihan' => $this->nominal_potongan_tagihan,
            'nominal_ke_saldo' => $this->nominal_ke_saldo,
            'biaya_midtrans' => $this->biaya_midtrans,
            'biaya_ditanggung_wali' => $this->biaya_ditanggung_wali,
            'snap_token' => $this->snap_token,
            'redirect_url' => $this->redirect_url,
            'payment_type' => $this->payment_type,
            'va_bank' => $this->va_bank,
            'va_number' => $this->va_number,
            'qr_url' => $this->qr_url,
            'expiry_time' => $this->expiry_time?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'kwitansi_id' => $this->kwitansi?->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

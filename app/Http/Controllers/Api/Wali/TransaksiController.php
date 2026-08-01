<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\TransaksiResource;
use App\Models\Santri;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
use Illuminate\Http\JsonResponse;

class TransaksiController extends WaliApiController
{
    public function index(Santri $santri): JsonResponse
    {
        $this->authorizedSantri($santri);

        // Riwayat wali adalah timeline keuangan santri, bukan hanya ledger
        // saldo belanja. Setoran tunai/Midtrans ke tabungan sebelumnya tidak
        // tampil karena disimpan pada ledger terpisah.
        $saldo = $santri->transaksis()
            ->with(['santri.lembaga', 'tagihan.jenisTagihan', 'referensi', 'kwitansi'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function (Transaksi $transaksi): array {
                return array_merge(
                    (new TransaksiResource($transaksi))->resolve(request()),
                    ['ledger' => 'saldo'],
                );
            });

        $tabungan = $santri->rekeningTabungan
            ? $santri->rekeningTabungan->transaksi()
            // Pemindahan dari saldo sudah memiliki pasangan debit pada
            // ledger saldo. Menampilkan keduanya akan menggandakan satu aksi.
            ->where('jenis', '!=', TransaksiTabungan::JENIS_SETORAN_DARI_SALDO)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (TransaksiTabungan $transaksi): array => [
                // ID negatif membedakan namespace ledger tanpa mengubah ID
                // asli yang tetap tersedia pada referensi UUID.
                'id' => -100000000 - $transaksi->id,
                'uuid' => $transaksi->uuid,
                'ledger' => 'tabungan',
                'jenis' => $transaksi->jenis,
                'arah' => $transaksi->arah,
                'nominal' => (int) $transaksi->nominal,
                'saldo_sebelum' => (int) $transaksi->saldo_sebelum,
                'saldo_sesudah' => (int) $transaksi->saldo_sesudah,
                'status' => $transaksi->status,
                'metode' => $transaksi->kanal,
                'metode_detail' => null,
                'biaya_midtrans' => null,
                'biaya_ditanggung_wali' => null,
                'catatan' => $transaksi->catatan,
                'created_at' => $transaksi->created_at?->toIso8601String(),
                'santri' => null,
                'kwitansi_id' => null,
                'tagihan' => null,
                'referensi' => null,
            ])
            : collect();

        $pembayaranTagihan = TagihanPembayaran::query()
            ->whereNull('transaksi_id')
            ->whereHas('tagihan', fn ($query) => $query->where('santri_id', $santri->id))
            ->with(['tagihan.jenisTagihan', 'kwitansi'])
            ->latest('dibayar_at')
            ->limit(100)
            ->get()
            ->map(function (TagihanPembayaran $pembayaran): array {
                $tagihan = $pembayaran->tagihan;

                return [
                    'id' => -200000000 - $pembayaran->id,
                    'uuid' => 'tagihan-'.$pembayaran->id,
                    'ledger' => 'tagihan',
                    'jenis' => 'pembayaran_tagihan',
                    'arah' => 'debit',
                    'nominal' => (int) $pembayaran->nominal,
                    'saldo_sebelum' => 0,
                    'saldo_sesudah' => 0,
                    'status' => 'berhasil',
                    'metode' => $pembayaran->sumber,
                    'metode_detail' => null,
                    'biaya_midtrans' => null,
                    'biaya_ditanggung_wali' => null,
                    'catatan' => null,
                    'created_at' => ($pembayaran->dibayar_at ?? $pembayaran->created_at)?->toIso8601String(),
                    'santri' => null,
                    'kwitansi_id' => $pembayaran->kwitansi?->id,
                    'tagihan' => [
                        'id' => $tagihan->id,
                        'jenis_tagihan_nama' => $tagihan->jenisTagihan?->nama,
                        'periode_label' => $tagihan->periode_label,
                        'nominal' => (int) $tagihan->nominal,
                        'nominal_terbayar' => (int) $tagihan->nominal_terbayar,
                        'sisa' => (int) $tagihan->sisa(),
                        'status' => $tagihan->status,
                    ],
                    'referensi' => null,
                ];
            });

        return response()->json([
            'data' => $saldo
                ->concat($tabungan)
                ->concat($pembayaranTagihan)
                ->sortByDesc('created_at')
                ->take(100)
                ->values(),
        ]);
    }

    public function show(Santri $santri, Transaksi $transaksi): TransaksiResource
    {
        $this->authorizedSantri($santri);
        abort_unless($transaksi->santri_id === $santri->id, 404);

        return new TransaksiResource(
            $transaksi->load(['santri.lembaga', 'tagihan.jenisTagihan', 'referensi', 'kwitansi'])
        );
    }
}

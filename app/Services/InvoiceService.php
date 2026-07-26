<?php

namespace App\Services;

use App\Models\Kwitansi;
use App\Models\PenarikanRequest;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\UnitUsahaPenarikan;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService
{
    private const JENIS_LABEL = [
        Transaksi::JENIS_TOPUP_TUNAI => 'Top Up Tunai',
        Transaksi::JENIS_TOPUP_TRANSFER_WALI => 'Top Up Transfer Wali (Midtrans)',
        Transaksi::JENIS_PENARIKAN_TUNAI => 'Penarikan Tunai',
        Transaksi::JENIS_PEMBAYARAN_TAGIHAN => 'Pembayaran Tagihan',
        Transaksi::JENIS_PENYESUAIAN => 'Penyesuaian Saldo',
    ];

    public function transaksi(Transaksi $transaksi): Response
    {
        $rows = [
            ['Arah', $transaksi->arah === Transaksi::ARAH_KREDIT ? 'Kredit (Masuk)' : 'Debit (Keluar)'],
            ['Metode', ucfirst(str_replace('_', ' ', $transaksi->metode))],
            ['Saldo Sebelum', $this->rupiah($transaksi->saldo_sebelum)],
            ['Saldo Sesudah', $this->rupiah($transaksi->saldo_sesudah)],
        ];

        if ($transaksi->tagihan) {
            $rows[] = ['Tagihan', $transaksi->tagihan->jenisTagihan->nama.' - '.$transaksi->tagihan->periode_label];
        }

        if ($transaksi->catatan) {
            $rows[] = ['Catatan', $transaksi->catatan];
        }

        return $this->build([
            'nomor' => 'TX-'.str_pad((string) $transaksi->id, 6, '0', STR_PAD_LEFT),
            'judul' => self::JENIS_LABEL[$transaksi->jenis] ?? ucfirst(str_replace('_', ' ', $transaksi->jenis)),
            'tanggal' => $transaksi->created_at,
            'santri' => $transaksi->santri,
            'rows' => $rows,
            'total' => $transaksi->nominal,
            'status' => 'Berhasil',
        ], 'invoice-transaksi-'.$transaksi->id.'.pdf');
    }

    public function topup(TopupWali $topup): Response
    {
        // A wali-borne fee is charged on top of nominal_diminta via
        // Midtrans (see TopupWaliService::chargeCoreApi()) - the receipt
        // total has to reflect what actually left the wali's bank/e-wallet,
        // or it reads as a discrepancy against their own bank statement.
        $biayaDibebankanWali = $topup->biaya_ditanggung_wali && $topup->biaya_midtrans > 0;
        $total = $topup->nominal_diminta + ($biayaDibebankanWali ? $topup->biaya_midtrans : 0);

        $rows = [
            ['Order ID', $topup->midtrans_order_id],
            ['Dibayar Oleh', $topup->user->name],
            ['Nominal Top Up', $this->rupiah($topup->nominal_diminta)],
        ];

        if ($biayaDibebankanWali) {
            $rows[] = ['Biaya Admin Midtrans', $this->rupiah($topup->biaya_midtrans)];
        }

        if ($topup->nominal_potongan_tagihan > 0) {
            $rows[] = ['Dipakai Bayar Tagihan', $this->rupiah($topup->nominal_potongan_tagihan)];
        }

        if ($topup->nominal_ke_saldo > 0) {
            $rows[] = ['Masuk ke Saldo', $this->rupiah($topup->nominal_ke_saldo)];
        }

        return $this->build([
            'nomor' => 'TOPUP-'.$topup->id,
            'judul' => 'Top Up Saldo via Midtrans',
            'tanggal' => $topup->paid_at ?? $topup->created_at,
            'santri' => $topup->santri,
            'rows' => $rows,
            'total' => $total,
            'status' => 'Lunas',
        ], 'invoice-topup-'.$topup->midtrans_order_id.'.pdf');
    }

    public function penarikan(PenarikanRequest $penarikan): Response
    {
        $rows = [
            ['Diminta Pada', $penarikan->diminta_at->format('d/m/Y H:i')],
        ];

        if ($penarikan->diprosesOleh) {
            $rows[] = ['Diproses Oleh', $penarikan->diprosesOleh->name];
        }

        return $this->build([
            'nomor' => 'PNRK-'.str_pad((string) $penarikan->id, 6, '0', STR_PAD_LEFT),
            'judul' => 'Penarikan Tunai',
            'tanggal' => $penarikan->diproses_at ?? $penarikan->diminta_at,
            'santri' => $penarikan->santri,
            'rows' => $rows,
            'total' => $penarikan->nominal_diminta,
            'status' => 'Selesai',
        ], 'invoice-penarikan-'.$penarikan->id.'.pdf');
    }

    public function tagihan(Tagihan $tagihan): Response
    {
        $rows = [
            ['Jenis Tagihan', $tagihan->jenisTagihan->nama],
            ['Periode', $tagihan->periode_label],
        ];

        if ($tagihan->diskon_persen) {
            $rows[] = ['Nominal Sebelum Diskon', $this->rupiah($tagihan->nominal_sebelum_diskon)];
            $rows[] = ['Diskon', $tagihan->diskon_persen.'%'.($tagihan->kategoriDiskon ? ' ('.$tagihan->kategoriDiskon->nama.')' : '')];
        }

        foreach ($tagihan->pembayarans as $pembayaran) {
            $rows[] = [
                'Dibayar '.$pembayaran->dibayar_at->format('d/m/Y H:i').' - '.(TagihanPembayaran::SUMBER_LABEL[$pembayaran->sumber] ?? $pembayaran->sumber),
                $this->rupiah($pembayaran->nominal),
            ];
        }

        return $this->build([
            'nomor' => 'TGH-'.str_pad((string) $tagihan->id, 6, '0', STR_PAD_LEFT),
            'judul' => 'Pembayaran Tagihan',
            'tanggal' => $tagihan->pembayarans->last()?->dibayar_at ?? $tagihan->updated_at,
            'santri' => $tagihan->santri,
            'rows' => $rows,
            'total' => $tagihan->nominal,
            'status' => 'Lunas',
        ], 'invoice-tagihan-'.$tagihan->id.'.pdf');
    }

    /**
     * Bukti pencairan for a fulfilled kantin withdrawal - same kwitansi
     * shape as penarikan() above, but there's no santri (a UnitUsaha is a
     * business, not a person) and the transfer reference is the whole
     * point of this document, so it's always shown as its own row.
     */
    public function kantinPenarikan(UnitUsahaPenarikan $penarikan): Response
    {
        $rows = [
            ['Diminta Pada', $penarikan->diminta_at->format('d/m/Y H:i')],
            ['Metode Pencairan', $penarikan->metodeLabel()],
        ];

        if ($penarikan->metode_pencairan === UnitUsahaPenarikan::METODE_TRANSFER_BANK) {
            $rows[] = ['Rekening Tujuan', trim(($penarikan->bank_nama_tujuan ?? '').' '.($penarikan->bank_no_rekening_tujuan ?? ''))];
            $rows[] = ['Atas Nama', $penarikan->bank_atas_nama_tujuan ?? '-'];
            $rows[] = ['Nomor Referensi Transfer', $penarikan->referensi_transfer ?? '-'];
        } else {
            $rows[] = ['Diserahkan Pada', $penarikan->diserahkan_at?->format('d/m/Y H:i') ?? '-'];
        }

        if ($penarikan->diprosesOleh) {
            $rows[] = ['Dicairkan Oleh', $penarikan->diprosesOleh->name];
        }

        if ($penarikan->dikonfirmasi_at) {
            $rows[] = ['Diterima/Dikonfirmasi Pada', $penarikan->dikonfirmasi_at->format('d/m/Y H:i')];
        }

        return $this->build([
            'nomor' => 'KNTN-PNRK-'.str_pad((string) $penarikan->id, 6, '0', STR_PAD_LEFT),
            'judul' => 'Penarikan Saldo Kantin',
            'tanggal' => $penarikan->diproses_at ?? $penarikan->diminta_at,
            'pihakLabel' => 'Kantin',
            'pihakNama' => $penarikan->unitUsaha->nama,
            'pihakKodeLabel' => 'Kode',
            'pihakKode' => $penarikan->unitUsaha->kode,
            'rows' => $rows,
            'total' => $penarikan->nominal_diminta,
            'status' => $penarikan->dikonfirmasi_at ? 'Diterima' : 'Menunggu Konfirmasi Penerima',
        ], 'invoice-kantin-penarikan-'.$penarikan->id.'.pdf');
    }

    /**
     * The official numbered receipt (kwitansi resmi) - unlike every method
     * above, whose "nomor" is derived fresh from the record's own id on
     * every render, this reuses the number a Kwitansi row was assigned once
     * at issuance time (see KwitansiService), so re-downloading the same
     * kwitansi later always shows the same number.
     */
    public function kwitansi(Kwitansi $kwitansi): Response
    {
        if ($kwitansi->jenis === Kwitansi::JENIS_TAGIHAN) {
            $pembayaran = $kwitansi->tagihanPembayaran;
            $tagihan = $pembayaran->tagihan;

            $rows = [
                ['Jenis Tagihan', $tagihan->jenisTagihan->nama],
                ['Periode', $tagihan->periode_label],
                ['Sumber Pembayaran', TagihanPembayaran::SUMBER_LABEL[$pembayaran->sumber] ?? $pembayaran->sumber],
            ];

            return $this->build([
                'nomor' => $kwitansi->nomor_kwitansi,
                'judul' => 'Pembayaran Tagihan',
                'tanggal' => $pembayaran->dibayar_at,
                'santri' => $kwitansi->santri,
                'rows' => $rows,
                'total' => $kwitansi->nominal,
                'status' => 'Lunas',
                'resmi' => true,
            ], 'kwitansi-'.$kwitansi->nomor_kwitansi.'.pdf');
        }

        if ($kwitansi->jenis === Kwitansi::JENIS_TOPUP) {
            $topup = $kwitansi->topupWali;
            $biayaWali = $topup->biaya_ditanggung_wali && $topup->biaya_midtrans > 0;
            $rows = [
                ['Order ID', $topup->midtrans_order_id],
                ['Metode', $topup->metodeLabel()],
                ['Nominal Top Up', $this->rupiah($topup->nominal_diminta)],
            ];

            if ($biayaWali) {
                $rows[] = ['Biaya Admin Midtrans', $this->rupiah($topup->biaya_midtrans)];
            }

            return $this->build([
                'nomor' => $kwitansi->nomor_kwitansi,
                'judul' => 'Top Up Saldo',
                'tanggal' => $topup->paid_at,
                'santri' => $kwitansi->santri,
                'rows' => $rows,
                'total' => $topup->nominal_diminta + ($biayaWali ? $topup->biaya_midtrans : 0),
                'status' => 'Berhasil',
                'resmi' => true,
            ], 'kwitansi-'.$kwitansi->nomor_kwitansi.'.pdf');
        }

        $transaksi = $kwitansi->transaksi;
        $unitUsaha = $transaksi->referensi;

        $rows = [
            ['Kantin', $unitUsaha?->nama ?? '-'],
        ];

        return $this->build([
            'nomor' => $kwitansi->nomor_kwitansi,
            'judul' => 'Pembayaran Kantin',
            'tanggal' => $transaksi->created_at,
            'santri' => $kwitansi->santri,
            'rows' => $rows,
            'total' => $kwitansi->nominal,
            'status' => 'Berhasil',
            'resmi' => true,
        ], 'kwitansi-'.$kwitansi->nomor_kwitansi.'.pdf');
    }

    private function rupiah(int $nominal): string
    {
        return 'Rp '.number_format($nominal, 0, ',', '.');
    }

    private function build(array $data, string $filename): Response
    {
        $data['pihakLabel'] ??= 'Santri';
        $data['pihakKodeLabel'] ??= 'NIS';
        $data['pihakNama'] ??= ($data['santri'] ?? null)?->nama;
        $data['pihakKode'] ??= ($data['santri'] ?? null)?->nis;
        $data['santri'] ??= null;
        $data['resmi'] ??= false;

        return Pdf::loadView('pdf.invoice', $data)->download($filename);
    }
}

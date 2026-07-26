<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Symfony\Component\HttpFoundation\Response;

class KeluargaController extends Controller
{
    /**
     * Streamed as a real GET download (not returned directly from the
     * Livewire action) because DomPDF's response type isn't recognized by
     * Livewire's automatic file-download detection the way Excel's is -
     * Admin\Keluarga\Index::bulkBuatAkunWali() stashes the just-created rows
     * in the session and redirects here.
     */
    public function unduhAkunWaliBaru(ReportService $reports): Response
    {
        $rows = session()->pull('akun_wali_baru_pdf');

        abort_if(empty($rows), 404, 'Tidak ada daftar akun wali baru untuk diunduh.');

        return $reports->pdf(
            'Daftar Akun Wali Baru ('.count($rows).' akun)',
            ['Nama Kepala Keluarga', 'No. KK (Login & Kata Sandi Awal)', 'Jumlah Santri'],
            $rows,
            'Kata sandi wajib diganti oleh wali saat pertama kali login.',
            'akun-wali-baru-'.now()->format('Ymd-His').'.pdf'
        );
    }
}

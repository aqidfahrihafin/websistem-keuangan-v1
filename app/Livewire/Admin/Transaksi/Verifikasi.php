<?php

namespace App\Livewire\Admin\Transaksi;

use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Verifikasi extends Component
{
    public string $nis = '';

    public ?Santri $santri = null;

    public ?int $nominal = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function cariSantri(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
        $this->resetErrorBag();
        $this->santri = Santri::where('nis', $this->nis)->first();

        if (! $this->santri) {
            $this->addError('nis', 'Santri dengan NIS tersebut tidak ditemukan.');
            $this->errorMessage = 'Pencarian gagal. Santri dengan NIS tersebut tidak ditemukan.';

            return;
        }

        $this->santri->load('saldo');
        $this->statusMessage = "Santri {$this->santri->nama} berhasil ditemukan.";
    }

    public function catatSetoran(WalletService $wallet): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
        $this->validate(['nominal' => ['required', 'integer', 'min:1']]);

        if (! $this->santri) {
            $this->addError('nis', 'Cari santri terlebih dahulu.');
            $this->errorMessage = 'Setoran belum dapat dicatat. Cari santri terlebih dahulu.';

            return;
        }

        $nominal = $this->nominal;

        try {
            $wallet->credit($this->santri, $nominal, Transaksi::JENIS_TOPUP_TUNAI, [
                'metode' => Transaksi::METODE_TUNAI,
                'diproses_oleh' => auth()->id(),
            ]);

            $this->santri->refresh()->load('saldo');
            $this->statusMessage = 'Setoran tunai Rp '.number_format($nominal, 0, ',', '.')
                ." untuk {$this->santri->nama} berhasil dicatat. Saldo terbaru Rp "
                .number_format($this->santri->saldo?->saldo ?? 0, 0, ',', '.').'.';
            $this->nominal = null;
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Setoran tunai gagal dicatat. Saldo tidak diubah. Silakan coba kembali.';
        }
    }

    public function render()
    {
        return view('livewire.admin.transaksi.verifikasi', ['title' => 'Catat Setoran Tunai']);
    }
}

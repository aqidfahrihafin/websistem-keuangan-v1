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

    public function cariSantri(): void
    {
        $this->santri = Santri::where('nis', $this->nis)->first();

        if (! $this->santri) {
            $this->addError('nis', 'Santri dengan NIS tersebut tidak ditemukan.');
        }
    }

    public function catatSetoran(WalletService $wallet): void
    {
        $this->validate(['nominal' => ['required', 'integer', 'min:1']]);

        if (! $this->santri) {
            $this->addError('nis', 'Cari santri terlebih dahulu.');

            return;
        }

        $wallet->credit($this->santri, $this->nominal, Transaksi::JENIS_TOPUP_TUNAI, [
            'metode' => Transaksi::METODE_TUNAI,
            'diproses_oleh' => auth()->id(),
        ]);

        session()->flash('status', 'Setoran tunai berhasil dicatat dan saldo santri sudah diperbarui.');
        $this->nominal = null;
    }

    public function render()
    {
        return view('livewire.admin.transaksi.verifikasi', ['title' => 'Catat Setoran Tunai']);
    }
}

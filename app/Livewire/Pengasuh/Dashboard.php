<?php

namespace App\Livewire\Pengasuh;

use App\Models\SaldoSantri;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\MidtransSettingApproval;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.pengasuh.dashboard', [
            'title' => 'Dashboard Pengasuh',
            'totalSantriAktif' => Santri::where('status', Santri::STATUS_AKTIF)->count(),
            'totalSaldo' => SaldoSantri::sum('saldo'),
            'tagihanBelumLunas' => Tagihan::whereIn('status', [Tagihan::STATUS_BELUM_LUNAS, Tagihan::STATUS_SEBAGIAN])->count(),
            'transaksiHariIni' => Transaksi::whereDate('created_at', now()->toDateString())->count(),
            'persetujuanMidtrans' => MidtransSettingApproval::where('status', MidtransSettingApproval::STATUS_PENDING)
                ->where('expires_at', '>', now())->count(),
        ]);
    }
}

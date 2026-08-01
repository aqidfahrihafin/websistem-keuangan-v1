<?php

namespace App\Livewire\Pengasuh;

use App\Models\MidtransSettingApproval;
use App\Services\MidtransApprovalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts::app')]
class PersetujuanMidtrans extends Component
{
    use WithPagination;

    public string $passwordKonfirmasi = '';
    public string $alasanPenolakan = '';
    public ?string $statusMessage = null;
    public ?string $errorMessage = null;

    public function setujui(int $id, MidtransApprovalService $service): void
    {
        $this->review($id, true, $service);
    }

    public function tolak(int $id, MidtransApprovalService $service): void
    {
        $this->validate(['alasanPenolakan' => ['required', 'string', 'min:5', 'max:500']]);
        $this->review($id, false, $service);
    }

    private function review(int $id, bool $approve, MidtransApprovalService $service): void
    {
        $this->statusMessage = $this->errorMessage = null;
        $key = 'midtrans-approval:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->errorMessage = 'Terlalu banyak percobaan. Coba kembali dalam '.RateLimiter::availableIn($key).' detik.';
            return;
        }
        RateLimiter::hit($key, 60);
        $this->validate(['passwordKonfirmasi' => ['required', 'current_password']]);
        RateLimiter::clear($key);

        try {
            $approval = MidtransSettingApproval::findOrFail($id);
            $approve
                ? $service->approve($approval, Auth::user())
                : $service->reject($approval, Auth::user(), $this->alasanPenolakan);
            $this->statusMessage = $approve
                ? 'Perubahan disetujui dan konfigurasi baru sudah aktif.'
                : 'Pengajuan berhasil ditolak.';
            $this->reset(['passwordKonfirmasi', 'alasanPenolakan']);
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        MidtransSettingApproval::query()
            ->where('status', MidtransSettingApproval::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => MidtransSettingApproval::STATUS_EXPIRED]);

        return view('livewire.pengasuh.persetujuan-midtrans', [
            'title' => 'Persetujuan Midtrans',
            'pengajuan' => MidtransSettingApproval::query()->with(['requester', 'reviewer'])->latest()->paginate(10),
        ]);
    }
}

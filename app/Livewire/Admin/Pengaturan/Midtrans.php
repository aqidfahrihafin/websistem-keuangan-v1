<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Services\MidtransFeeService;
use App\Services\MidtransApprovalService;
use App\Services\MidtransSettingsService;
use App\Services\SaldoFloorService;
use App\Services\TopupWaliService;
use App\Models\MidtransSettingApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Midtrans extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    // Deliberately left blank on mount rather than pre-filled with the real
    // secret - Livewire serializes public properties into the page's HTML/
    // wire snapshot, so pre-filling would leak the live Server Key to
    // anyone with view-source access even though the <input> itself is
    // type="password". Blank + submitted blank means "keep the existing
    // key unchanged"; only a non-blank submission replaces it.
    public ?string $server_key = null;

    public bool $has_server_key = false;

    public ?string $client_key = null;

    public bool $is_production = false;

    public int $minimal_saldo_bayar_tagihan = 0;

    public int $maksimal_nominal_transaksi = 0;

    public bool $biaya_dibebankan_wali_topup = false;

    public bool $biaya_dibebankan_wali_tagihan = false;

    public string $biaya_bni_va_tipe = MidtransFeeService::TIPE_TETAP;

    public float $biaya_bni_va_nilai = 0;

    public string $biaya_bca_va_tipe = MidtransFeeService::TIPE_TETAP;

    public float $biaya_bca_va_nilai = 0;

    public string $biaya_bri_va_tipe = MidtransFeeService::TIPE_TETAP;

    public float $biaya_bri_va_nilai = 0;

    public string $biaya_qris_tipe = MidtransFeeService::TIPE_TETAP;

    public float $biaya_qris_nilai = 0;

    public string $password_confirmasi = '';

    // Rendered inside this component's own view (not session()->flash(),
    // which only becomes visible after a full page navigation - Livewire
    // form submits are AJAX round trips that re-render just this component,
    // never the surrounding layout where a flash banner would live). Reset
    // at the top of simpan() every attempt so a stale message never lingers
    // across tries.
    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    // Snapshot of what's actually saved right now, separate from the
    // editable *_tipe/*_nilai properties above (which start from the same
    // values but drift the moment the admin edits the form) - lets the
    // "Pengaturan tersimpan saat ini" summary keep showing the real saved
    // state even while a draft edit is in progress.
    public array $biayaTersimpan = [];

    public bool $dibebankanWaliTopupTersimpan = false;

    public bool $dibebankanWaliTagihanTersimpan = false;

    public function mount(MidtransSettingsService $service, SaldoFloorService $saldoFloor, MidtransFeeService $feeService): void
    {
        $this->has_server_key = filled($service->serverKey());
        $this->client_key = $service->clientKey();
        $this->is_production = $service->isProduction();
        $this->minimal_saldo_bayar_tagihan = $saldoFloor->minimal();
        $this->maksimal_nominal_transaksi = $saldoFloor->maksimalNominal();

        $this->biaya_dibebankan_wali_topup = $feeService->dibebankanWali();
        $this->biaya_dibebankan_wali_tagihan = $feeService->dibebankanWali(untukTagihan: true);
        $channel = $feeService->semuaChannel();
        $this->biaya_bni_va_tipe = $channel[TopupWaliService::METODE_BNI_VA]['tipe'];
        $this->biaya_bni_va_nilai = $channel[TopupWaliService::METODE_BNI_VA]['nilai'];
        $this->biaya_bca_va_tipe = $channel[TopupWaliService::METODE_BCA_VA]['tipe'];
        $this->biaya_bca_va_nilai = $channel[TopupWaliService::METODE_BCA_VA]['nilai'];
        $this->biaya_bri_va_tipe = $channel[TopupWaliService::METODE_BRI_VA]['tipe'];
        $this->biaya_bri_va_nilai = $channel[TopupWaliService::METODE_BRI_VA]['nilai'];
        $this->biaya_qris_tipe = $channel[TopupWaliService::METODE_QRIS]['tipe'];
        $this->biaya_qris_nilai = $channel[TopupWaliService::METODE_QRIS]['nilai'];

        $this->biayaTersimpan = $channel;
        $this->dibebankanWaliTopupTersimpan = $this->biaya_dibebankan_wali_topup;
        $this->dibebankanWaliTagihanTersimpan = $this->biaya_dibebankan_wali_tagihan;
    }

    /**
     * Saving Midtrans credentials moves real money, so this goes through
     * extra friction beyond the route's role:admin gate: the admin must
     * re-type their own password every time (Laravel's `current_password`
     * rule, checked against the authenticated guard - catches a hijacked/
     * left-open admin session), attempts are rate-limited to blunt password
     * guessing, and the change is written to the activity log (already used
     * for backups) so there's an audit trail of who changed what and when -
     * without ever logging the secret values themselves.
     */
    public function simpan(MidtransSettingsService $service, MidtransApprovalService $approvals): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;

        $throttleKey = 'midtrans-settings:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $pesan = 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.';
            $this->addError('password_confirmasi', $pesan);
            $this->errorMessage = $pesan;

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        $tipeRule = Rule::in([MidtransFeeService::TIPE_TETAP, MidtransFeeService::TIPE_PERSEN]);

        // A "persen" nilai is a percentage of the topup amount, so it's
        // capped at 100 - a "tetap" nilai is a flat Rupiah amount, capped
        // only loosely as a fat-finger guard. Checked against the sibling
        // *_tipe property directly (already set on $this via wire:model by
        // the time simpan() runs) rather than a static rule, since the
        // valid range depends on which tipe was chosen.
        $nilaiRule = fn (string $tipeProperty) => function (string $attribute, $value, $fail) use ($tipeProperty) {
            $max = $this->{$tipeProperty} === MidtransFeeService::TIPE_PERSEN ? 100 : 100_000_000;

            if ($value < 0 || $value > $max) {
                $fail("Nilai harus antara 0 dan {$max}.");
            }
        };

        try {
            $data = $this->validate([
                'server_key' => ['nullable', 'string'],
                'client_key' => ['required', 'string'],
                'is_production' => ['boolean'],
                'minimal_saldo_bayar_tagihan' => ['required', 'integer', 'min:0'],
                'maksimal_nominal_transaksi' => ['required', 'integer', 'min:1', 'gt:minimal_saldo_bayar_tagihan'],
                'biaya_dibebankan_wali_topup' => ['boolean'],
                'biaya_dibebankan_wali_tagihan' => ['boolean'],
                'biaya_bni_va_tipe' => ['required', $tipeRule],
                'biaya_bni_va_nilai' => ['required', 'numeric', $nilaiRule('biaya_bni_va_tipe')],
                'biaya_bca_va_tipe' => ['required', $tipeRule],
                'biaya_bca_va_nilai' => ['required', 'numeric', $nilaiRule('biaya_bca_va_tipe')],
                'biaya_bri_va_tipe' => ['required', $tipeRule],
                'biaya_bri_va_nilai' => ['required', 'numeric', $nilaiRule('biaya_bri_va_tipe')],
                'biaya_qris_tipe' => ['required', $tipeRule],
                'biaya_qris_nilai' => ['required', 'numeric', $nilaiRule('biaya_qris_tipe')],
                'password_confirmasi' => ['required', 'current_password'],
            ], [], [
                'password_confirmasi' => 'kata sandi',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-thrown as-is straight after - Livewire's own handler still
            // needs this exception to populate $errors for the inline
            // per-field messages below each input. This just also surfaces
            // a page-level banner so a failure is never silent even if the
            // admin doesn't scroll down to see which field is highlighted.
            $this->errorMessage = 'Gagal menyimpan. Periksa kembali isian yang ditandai di bawah.';

            throw $e;
        }

        RateLimiter::clear($throttleKey);

        $existingServerKey = $service->serverKey();

        if (blank($data['server_key']) && blank($existingServerKey)) {
            $pesan = 'Server Key wajib diisi.';
            $this->addError('server_key', $pesan);
            $this->errorMessage = 'Gagal menyimpan. '.$pesan;

            return;
        }

        $serverKey = filled($data['server_key']) ? $data['server_key'] : $existingServerKey;

        try {
            $approvals->request(Auth::user(), [
                'server_key' => $serverKey,
                'client_key' => $data['client_key'],
                'is_production' => (bool) $data['is_production'],
                'minimal_saldo' => $data['minimal_saldo_bayar_tagihan'],
                'maksimal_nominal' => $data['maksimal_nominal_transaksi'],
                'fee_wali_topup' => (bool) $data['biaya_dibebankan_wali_topup'],
                'fee_wali_tagihan' => (bool) $data['biaya_dibebankan_wali_tagihan'],
                'channels' => [
                    TopupWaliService::METODE_BNI_VA => ['tipe' => $data['biaya_bni_va_tipe'], 'nilai' => (float) $data['biaya_bni_va_nilai']],
                    TopupWaliService::METODE_BCA_VA => ['tipe' => $data['biaya_bca_va_tipe'], 'nilai' => (float) $data['biaya_bca_va_nilai']],
                    TopupWaliService::METODE_BRI_VA => ['tipe' => $data['biaya_bri_va_tipe'], 'nilai' => (float) $data['biaya_bri_va_nilai']],
                    TopupWaliService::METODE_QRIS => ['tipe' => $data['biaya_qris_tipe'], 'nilai' => (float) $data['biaya_qris_nilai']],
                ],
            ]);
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        $this->password_confirmasi = '';
        $this->server_key = null;
        $this->statusMessage = 'Pengajuan perubahan berhasil dikirim dan menunggu persetujuan pengasuh.';
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.midtrans', [
            'title' => 'Pengaturan Midtrans',
            'pengajuanTerakhir' => MidtransSettingApproval::query()
                ->where('requested_by', Auth::id())
                ->with('reviewer')
                ->latest()
                ->first(),
            'jumlahPengasuh' => \App\Models\User::whereHas('roles', fn ($query) => $query->where('name', 'pengasuh'))->count(),
        ]);
    }
}

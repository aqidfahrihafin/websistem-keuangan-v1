<?php

namespace App\Services;

use App\Models\MidtransSettingApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MidtransApprovalService
{
    public function __construct(
        private MidtransSettingsService $midtrans,
        private MidtransFeeService $fees,
        private SaldoFloorService $limits,
    ) {}

    public function current(): array
    {
        return [
            'server_key' => $this->midtrans->serverKey(),
            'client_key' => $this->midtrans->clientKey(),
            'is_production' => $this->midtrans->isProduction(),
            'minimal_saldo' => $this->limits->minimal(),
            'maksimal_nominal' => $this->limits->maksimalNominal(),
            'fee_wali_topup' => $this->fees->dibebankanWali(),
            'fee_wali_tagihan' => $this->fees->dibebankanWali(untukTagihan: true),
            'channels' => $this->fees->semuaChannel(),
        ];
    }

    public function request(User $requester, array $proposed): MidtransSettingApproval
    {
        $current = $this->current();
        $changes = $this->changes($current, $proposed);

        if ($changes === []) {
            throw new RuntimeException('Tidak ada perubahan pengaturan untuk diajukan.');
        }

        return DB::transaction(function () use ($requester, $current, $proposed, $changes) {
            MidtransSettingApproval::query()
                ->where('requested_by', $requester->id)
                ->where('status', MidtransSettingApproval::STATUS_PENDING)
                ->update(['status' => MidtransSettingApproval::STATUS_CANCELLED, 'review_note' => 'Digantikan pengajuan yang lebih baru.']);

            $approval = MidtransSettingApproval::create([
                'requested_by' => $requester->id,
                'status' => MidtransSettingApproval::STATUS_PENDING,
                'payload' => $proposed,
                'changes' => $changes,
                'base_hash' => $this->hash($current),
                'expires_at' => now()->addDay(),
            ]);

            activity('pengaturan')->causedBy($requester)
                ->performedOn($approval)
                ->withProperties(['approval_id' => $approval->id, 'changes' => array_keys($changes)])
                ->log('Mengajukan perubahan pengaturan Midtrans');

            return $approval;
        });
    }

    public function approve(MidtransSettingApproval $approval, User $reviewer): MidtransSettingApproval
    {
        $this->expireIfNeeded($approval);

        return DB::transaction(function () use ($approval, $reviewer) {
            $approval = MidtransSettingApproval::query()->lockForUpdate()->findOrFail($approval->id);
            $this->assertReviewable($approval, $reviewer);

            if (! hash_equals($approval->base_hash, $this->hash($this->current()))) {
                throw new RuntimeException('Pengaturan aktif sudah berubah sejak pengajuan dibuat. Minta admin membuat pengajuan baru.');
            }

            $data = $approval->payload;
            $this->midtrans->save($data['server_key'], $data['client_key'], (bool) $data['is_production']);
            $this->limits->simpan((int) $data['minimal_saldo']);
            $this->limits->simpanMaksimalNominal((int) $data['maksimal_nominal']);
            $this->fees->save((bool) $data['fee_wali_topup'], (bool) $data['fee_wali_tagihan'], $data['channels']);

            $approval->update([
                'status' => MidtransSettingApproval::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            activity('pengaturan')->causedBy($reviewer)->performedOn($approval)
                ->withProperties(['approval_id' => $approval->id, 'changes' => array_keys($approval->changes)])
                ->log('Menyetujui dan mengaktifkan perubahan pengaturan Midtrans');

            return $approval->fresh();
        });
    }

    public function reject(MidtransSettingApproval $approval, User $reviewer, string $reason): MidtransSettingApproval
    {
        $this->expireIfNeeded($approval);

        return DB::transaction(function () use ($approval, $reviewer, $reason) {
            $approval = MidtransSettingApproval::query()->lockForUpdate()->findOrFail($approval->id);
            $this->assertReviewable($approval, $reviewer);

            $approval->update([
                'status' => MidtransSettingApproval::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'review_note' => trim($reason),
                'reviewed_at' => now(),
            ]);

            activity('pengaturan')->causedBy($reviewer)->performedOn($approval)
                ->withProperties(['approval_id' => $approval->id])
                ->log('Menolak perubahan pengaturan Midtrans');

            return $approval->fresh();
        });
    }

    private function assertReviewable(MidtransSettingApproval $approval, User $reviewer): void
    {
        if (! $reviewer->hasRole('pengasuh')) {
            throw new RuntimeException('Hanya pengasuh yang dapat meninjau pengajuan ini.');
        }
        if ($approval->requested_by === $reviewer->id) {
            throw new RuntimeException('Pembuat pengajuan tidak dapat menyetujui pengajuannya sendiri.');
        }
        if ($approval->status !== MidtransSettingApproval::STATUS_PENDING) {
            throw new RuntimeException('Pengajuan ini sudah tidak menunggu persetujuan.');
        }
    }

    private function expireIfNeeded(MidtransSettingApproval $approval): void
    {
        $approval->refresh();
        if ($approval->status === MidtransSettingApproval::STATUS_PENDING && $approval->expires_at->isPast()) {
            $approval->update(['status' => MidtransSettingApproval::STATUS_EXPIRED]);
            throw new RuntimeException('Pengajuan sudah kedaluwarsa. Minta admin mengajukan ulang.');
        }
    }

    private function hash(array $settings): string
    {
        return hash('sha256', json_encode($settings, JSON_THROW_ON_ERROR));
    }

    private function changes(array $old, array $new): array
    {
        $labels = [
            'server_key' => 'Server Key', 'client_key' => 'Client Key', 'is_production' => 'Mode Midtrans',
            'minimal_saldo' => 'Saldo minimum', 'maksimal_nominal' => 'Batas transaksi',
            'fee_wali_topup' => 'Penanggung biaya top up', 'fee_wali_tagihan' => 'Penanggung biaya tagihan',
        ];
        $changes = [];
        foreach ($labels as $key => $label) {
            if (($old[$key] ?? null) !== ($new[$key] ?? null)) {
                $secret = in_array($key, ['server_key', 'client_key'], true);
                $changes[$key] = ['label' => $label, 'old' => $secret ? 'Tersimpan' : ($old[$key] ?? null), 'new' => $secret ? 'Diubah' : ($new[$key] ?? null)];
            }
        }
        foreach ($new['channels'] as $channel => $value) {
            if (($old['channels'][$channel] ?? null) !== $value) {
                $changes['channel_'.$channel] = ['label' => 'Biaya '.strtoupper(str_replace('_', ' ', $channel)), 'old' => $old['channels'][$channel] ?? null, 'new' => $value];
            }
        }
        return $changes;
    }
}

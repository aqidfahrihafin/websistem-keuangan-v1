<?php

namespace App\Services;

use App\Models\User;
use App\Models\WaliDeviceToken;
use App\Models\WaliNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

/**
 * Never throws to its caller - a push notification failing must never break
 * the financial operation (topup, debit, penarikan, tagihan) that triggered
 * it. Every call site can fire-and-forget this.
 */
class PushNotificationService
{
    public function notify(User $user, string $title, string $body, array $data = []): void
    {
        try {
            WaliNotification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'type' => (string) ($data['type'] ?? 'info'),
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal menyimpan notifikasi wali.', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }

        $tokens = $user->deviceTokens()->pluck('fcm_token', 'id');

        if ($tokens->isEmpty()) {
            return;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $report = Firebase::messaging()->sendMulticast($message, $tokens->values()->all());

            $unreachable = array_merge($report->unknownTokens(), $report->invalidTokens());

            if ($unreachable !== []) {
                WaliDeviceToken::query()->whereIn('fcm_token', $unreachable)->delete();
            }
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim push notification.', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

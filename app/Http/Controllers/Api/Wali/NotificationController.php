<?php

namespace App\Http\Controllers\Api\Wali;

use App\Models\WaliNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends WaliApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $request->user()
                ->waliNotifications()
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (WaliNotification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'type' => $notification->type,
                    'data' => $notification->data ?? [],
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ]);

            return response()->json([
                'data' => $notifications,
                'unread_count' => $request->user()
                    ->waliNotifications()
                    ->whereNull('read_at')
                    ->count(),
            ]);
        } catch (QueryException) {
            return response()->json([
                'message' => 'Pusat notifikasi sedang disiapkan. Silakan coba lagi beberapa saat.',
            ], 503);
        }
    }

    public function read(Request $request, WaliNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->waliNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}

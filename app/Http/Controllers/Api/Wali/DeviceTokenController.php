<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Models\WaliDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Upserts by fcm_token (not user_id) - a reinstall or a relogin as a
     * different wali on the same physical device reuses the same token, and
     * it should always end up pointing at whoever is currently signed in.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
        ]);

        WaliDeviceToken::query()->updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Device token tersimpan.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $request->user()->deviceTokens()->where('fcm_token', $data['fcm_token'])->delete();

        return response()->json(['message' => 'Device token dihapus.']);
    }
}

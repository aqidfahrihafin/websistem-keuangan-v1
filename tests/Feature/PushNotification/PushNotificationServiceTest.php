<?php

use App\Models\User;
use App\Models\WaliDeviceToken;
use App\Services\PushNotificationService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Kreait\Laravel\Firebase\Facades\Firebase;

it('sends a multicast push to every device token registered to the user', function () {
    $user = User::factory()->create();
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-a', 'platform' => 'android']);
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-b', 'platform' => 'android']);

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('sendMulticast')
        ->once()
        ->withArgs(fn ($message, $tokens) => in_array('token-a', $tokens, true) && in_array('token-b', $tokens, true))
        ->andReturn(MulticastSendReport::withItems([
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-a'), []),
            SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-b'), []),
        ]));

    Firebase::shouldReceive('messaging')->once()->andReturn($messaging);

    app(PushNotificationService::class)->notify($user, 'Judul', 'Isi pesan');

    expect(WaliDeviceToken::count())->toBe(2);
});

it('deletes device tokens Firebase reports as invalid or unregistered, self-healing on top of explicit unregister', function () {
    $user = User::factory()->create();
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-good', 'platform' => 'android']);
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-unknown', 'platform' => 'android']);
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-invalid', 'platform' => 'android']);

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('sendMulticast')->once()->andReturn(MulticastSendReport::withItems([
        SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'token-good'), []),
        SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, 'token-unknown'), NotFound::becauseTokenNotFound('token-unknown')),
        SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, 'token-invalid'), new InvalidArgument('Invalid registration token.')),
    ]));

    Firebase::shouldReceive('messaging')->once()->andReturn($messaging);

    app(PushNotificationService::class)->notify($user, 'Judul', 'Isi pesan');

    expect(WaliDeviceToken::pluck('fcm_token')->all())->toBe(['token-good']);
});

it('never contacts Firebase when the user has no registered device tokens', function () {
    $user = User::factory()->create();

    Firebase::shouldReceive('messaging')->never();

    app(PushNotificationService::class)->notify($user, 'Judul', 'Isi pesan');

    expect(WaliDeviceToken::count())->toBe(0);
});

it('swallows Firebase failures instead of throwing, so it never breaks the caller', function () {
    $user = User::factory()->create();
    WaliDeviceToken::create(['user_id' => $user->id, 'fcm_token' => 'token-x', 'platform' => 'android']);

    Firebase::shouldReceive('messaging')->once()->andThrow(new RuntimeException('Firebase tidak bisa dihubungi'));

    app(PushNotificationService::class)->notify($user, 'Judul', 'Isi pesan');

    expect(WaliDeviceToken::count())->toBe(1);
});

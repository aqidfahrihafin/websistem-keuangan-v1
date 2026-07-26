<?php

use Illuminate\Support\Facades\Artisan;

it('returns 404 when CRON_SECRET is not configured', function () {
    config(['app.cron_secret' => null]);

    $this->get('/cron/schedule/anything')->assertNotFound();
    $this->get('/cron/queue/anything')->assertNotFound();
});

it('returns 404 when the secret in the URL is wrong', function () {
    config(['app.cron_secret' => 'the-real-secret']);

    $this->get('/cron/schedule/wrong-secret')->assertNotFound();
    $this->get('/cron/queue/wrong-secret')->assertNotFound();
});

it('runs the scheduler when the secret matches', function () {
    config(['app.cron_secret' => 'the-real-secret']);

    Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn('');

    $this->get('/cron/schedule/the-real-secret')->assertOk();
});

it('runs the queue worker when the secret matches', function () {
    config(['app.cron_secret' => 'the-real-secret']);

    Artisan::shouldReceive('call')->once()->with('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 50,
    ])->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn('');

    $this->get('/cron/queue/the-real-secret')->assertOk();
});

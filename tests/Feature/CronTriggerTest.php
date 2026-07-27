<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config()->set('app.cron_secret', 'test-cron-secret');
});

it('menonaktifkan endpoint cron saat secret belum dikonfigurasi', function () {
    config()->set('app.cron_secret');

    $this->get('/cron/schedule/apapun')->assertNotFound();
    $this->get('/cron/queue/apapun')->assertNotFound();
});

it('menolak cron saat secret tidak cocok', function () {
    $this->get('/cron/schedule/salah')->assertNotFound();
});

it('menjalankan scheduler dan mengembalikan status berhasil', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('schedule:run', [])
        ->andReturn(0);
    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('No scheduled commands are ready to run.');

    $this->get('/cron/schedule/test-cron-secret')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'command' => 'schedule:run',
            'message' => 'No scheduled commands are ready to run.',
        ])
        ->assertJsonStructure(['ran_at']);
});

it('memberi status server error ketika worker antrean gagal', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 20,
            '--sleep' => 1,
            '--tries' => 3,
        ])
        ->andReturn(1);
    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Database connection failed.');

    $this->get('/cron/queue/test-cron-secret')
        ->assertInternalServerError()
        ->assertJson([
            'ok' => false,
            'command' => 'queue:work',
        ]);
});

it('menjalankan worker antrean dengan batas aman untuk request web', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 20,
            '--sleep' => 1,
            '--tries' => 3,
        ])
        ->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn('');

    $this->get('/cron/queue/test-cron-secret')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'command' => 'queue:work',
            'message' => 'Selesai.',
        ]);
});

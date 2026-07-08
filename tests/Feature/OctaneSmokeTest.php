<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Octane;

test('octane package and roadrunner server are configured for deployment', function () {
    expect(class_exists(Octane::class))->toBeTrue()
        ->and(config('octane.server'))->toBe('roadrunner');
});

test('octane request lifecycle listeners are registered', function () {
    $listeners = config('octane.listeners');

    expect($listeners)->toBeArray()
        ->and($listeners[RequestReceived::class] ?? null)->not->toBeNull()
        ->and($listeners[WorkerStarting::class] ?? null)->toContain(
            EnsureUploadedFilesAreValid::class,
        );
});

test('deploy health endpoint responds for octane smoke checks', function () {
    $this->get('/up')->assertOk();
});

test('octane artisan commands are registered', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('octane:start')
        ->and($commands)->toContain('octane:reload')
        ->and($commands)->toContain('octane:status');
});

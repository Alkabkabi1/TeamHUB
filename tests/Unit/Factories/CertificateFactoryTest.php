<?php

use App\Models\Certificate;

test('certificate factory creates unique certificate number on save', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->certificate_no)->toStartWith('CERT-')
        ->and($certificate->issued_at)->not->toBeNull()
        ->and($certificate->file_path)->toContain('certificates/')
        ->and($certificate->attendance)->not->toBeNull();
});

test('certificate factory links to checked in past event attendance', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->attendance->status)->toBe('checked_in')
        ->and($certificate->attendance->event->starts_at->isPast())->toBeTrue();
});

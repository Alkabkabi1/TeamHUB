<?php

use App\Enums\CertificateField;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;

test('certificate template factory creates a club-scoped draft by default', function () {
    $template = CertificateTemplate::factory()->create();

    expect($template->club)->not->toBeNull()
        ->and($template->status)->toBe('draft')
        ->and($template->is_default)->toBeFalse();
});

test('default state marks the template active and default', function () {
    $template = CertificateTemplate::factory()->default()->create();

    expect($template->status)->toBe('active')
        ->and($template->is_default)->toBeTrue();
});

test('withImage state attaches a background image to the template', function () {
    Storage::fake('public');

    $template = CertificateTemplate::factory()->withImage()->create();

    expect($template->getFirstMedia(CertificateTemplate::TEMPLATE_COLLECTION))->not->toBeNull()
        ->and($template->imagePath())->not->toBeNull();
});

test('placeholder factory links to a template and casts the binding enum', function () {
    $placeholder = CertificatePlaceholder::factory()->create();

    expect($placeholder->template)->not->toBeNull()
        ->and($placeholder->binding)->toBeInstanceOf(CertificateField::class);
});

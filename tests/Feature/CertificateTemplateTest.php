<?php

use App\Enums\CertificateField;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Create an approved club supervisor (holds the IssueCertificates capability).
 */
function makeCertificateSupervisor(Club $club): User
{
    $supervisor = User::factory()->clubSupervisor()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return $supervisor;
}

// ---------------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------------

test('guest cannot view certificate templates', function () {
    $club = Club::factory()->create();

    $this->get(route('certificate-templates.index', $club))
        ->assertRedirect(route('login'));
});

test('student gets 403 on the templates index', function () {
    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('certificate-templates.index', $club))
        ->assertForbidden();
});

test('supervisor can view the templates index', function () {
    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $this->actingAs($supervisor)
        ->get(route('certificate-templates.index', $club))
        ->assertOk();
});

test('student cannot create a template', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('certificate-templates.store', $club), [
            'name' => 'Hack',
            'image' => UploadedFile::fake()->image('t.png', 800, 600),
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

test('supervisor can create a template with an image and fields', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $this->actingAs($supervisor)
        ->post(route('certificate-templates.store', $club), [
            'name' => 'Participation certificate',
            'status' => 'active',
            'image' => UploadedFile::fake()->image('template.png', 1123, 794),
            'fields' => [
                [
                    'binding' => CertificateField::RecipientName->value,
                    'x' => 0.3,
                    'y' => 0.4,
                    'width' => 0.4,
                    'font_size' => 0.05,
                    'font_weight' => 'bold',
                    'color' => '#000000',
                    'align' => 'center',
                ],
                [
                    'binding' => CertificateField::StaticText->value,
                    'static_text' => 'Awarded to',
                    'x' => 0.1,
                    'y' => 0.8,
                    'width' => 0.3,
                    'font_size' => 0.03,
                    'align' => 'left',
                ],
            ],
        ])
        ->assertRedirect(route('certificate-templates.index', $club));

    $template = CertificateTemplate::where('club_id', $club->id)->first();

    expect($template)->not->toBeNull();
    expect($template->width)->toBe(1123);
    expect($template->height)->toBe(794);
    expect($template->getFirstMedia(CertificateTemplate::TEMPLATE_COLLECTION))->not->toBeNull();
    expect($template->placeholders()->count())->toBe(2);

    $this->assertDatabaseHas('certificate_placeholders', [
        'certificate_template_id' => $template->id,
        'binding' => CertificateField::StaticText->value,
        'static_text' => 'Awarded to',
    ]);
});

test('creating a template requires an image', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $this->actingAs($supervisor)
        ->post(route('certificate-templates.store', $club), [
            'name' => 'No image',
        ])
        ->assertSessionHasErrors('image');
});

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

test('updating a template replaces its placeholders', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $template = CertificateTemplate::factory()->withImage()->create(['club_id' => $club->id]);
    CertificatePlaceholder::factory()->count(3)->create([
        'certificate_template_id' => $template->id,
    ]);

    $this->actingAs($supervisor)
        ->put(route('certificate-templates.update', [$club, $template]), [
            'name' => 'Renamed',
            'fields' => [
                [
                    'binding' => CertificateField::EventTitle->value,
                    'x' => 0.2,
                    'y' => 0.5,
                    'width' => 0.6,
                    'font_size' => 0.04,
                    'align' => 'center',
                ],
            ],
        ])
        ->assertRedirect(route('certificate-templates.index', $club));

    $template->refresh();

    expect($template->name)->toBe('Renamed');
    expect($template->placeholders()->count())->toBe(1);
    expect($template->placeholders()->first()->binding)->toBe(CertificateField::EventTitle);
});

// ---------------------------------------------------------------------------
// Set default
// ---------------------------------------------------------------------------

test('setting a default unsets the previous default', function () {
    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $first = CertificateTemplate::factory()->default()->create(['club_id' => $club->id]);
    $second = CertificateTemplate::factory()->create(['club_id' => $club->id]);

    $this->actingAs($supervisor)
        ->post(route('certificate-templates.default', [$club, $second]))
        ->assertRedirect(route('certificate-templates.index', $club));

    expect($first->fresh()->is_default)->toBeFalse();
    expect($second->fresh()->is_default)->toBeTrue();
    expect($second->fresh()->status)->toBe('active');
});

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

test('supervisor can delete a template', function () {
    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $template = CertificateTemplate::factory()->create(['club_id' => $club->id]);

    $this->actingAs($supervisor)
        ->delete(route('certificate-templates.destroy', [$club, $template]))
        ->assertRedirect(route('certificate-templates.index', $club));

    $this->assertDatabaseMissing('certificate_templates', ['id' => $template->id]);
});

test('cannot manage a template belonging to another club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $template = CertificateTemplate::factory()->create(['club_id' => $otherClub->id]);

    $this->actingAs($supervisor)
        ->delete(route('certificate-templates.destroy', [$club, $template]))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Preview
// ---------------------------------------------------------------------------

test('supervisor can preview a template as a PDF', function () {
    Storage::fake('public');

    $club = Club::factory()->create();
    $supervisor = makeCertificateSupervisor($club);

    $template = CertificateTemplate::factory()->withImage()->create(['club_id' => $club->id]);
    CertificatePlaceholder::factory()
        ->binding(CertificateField::RecipientName)
        ->create(['certificate_template_id' => $template->id]);

    $response = $this->actingAs($supervisor)
        ->get(route('certificate-templates.preview', [$club, $template]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

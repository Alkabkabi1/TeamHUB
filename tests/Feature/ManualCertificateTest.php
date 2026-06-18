<?php

use App\Enums\CertificateField;
use App\Models\Certificate;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Build a club with an active default template and a supervisor who may issue
 * certificates for it.
 *
 * @return array{0: User, 1: Club, 2: CertificateTemplate}
 */
function clubWithCertificateSupervisor(): array
{
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create(['status' => 'active']);

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $template = giveClubDefaultTemplate($club);

    return [$supervisor, $club, $template];
}

test('supervisor can manually issue a standalone certificate (no activity)', function () {
    Storage::fake('public');
    Notification::fake();

    [$supervisor, $club, $template] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $template->id,
            'user_id' => $student->id,
        ])
        ->assertRedirect();

    $cert = Certificate::query()->where('user_id', $student->id)->first();

    expect($cert)->not->toBeNull();
    expect($cert->club_id)->toBe($club->id);
    expect($cert->certificate_template_id)->toBe($template->id);
    expect($cert->event_id)->toBeNull();
    Storage::disk('public')->assertExists($cert->file_path);

    Notification::assertSentToTimes($student, CertificateIssuedNotification::class, 1);
});

test('supervisor can manually issue a certificate tied to an activity', function () {
    Storage::fake('public');

    [$supervisor, $club, $template] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();
    $event = Event::factory()->past()->for($club)->create(['status' => 'active']);

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $template->id,
            'user_id' => $student->id,
            'event_id' => $event->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('certificates', [
        'user_id' => $student->id,
        'club_id' => $club->id,
        'certificate_template_id' => $template->id,
        'event_id' => $event->id,
    ]);
});

test('supervisor can issue a certificate from a chosen non-default template', function () {
    Storage::fake('public');

    [$supervisor, $club] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();

    $chosen = CertificateTemplate::factory()->active()->withImage()->create([
        'club_id' => $club->id,
    ]);
    CertificatePlaceholder::factory()
        ->binding(CertificateField::RecipientName)
        ->create(['certificate_template_id' => $chosen->id]);

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $chosen->id,
            'user_id' => $student->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('certificates', [
        'user_id' => $student->id,
        'certificate_template_id' => $chosen->id,
    ]);
});

test('manual issuance rejects an event that belongs to another club', function () {
    Storage::fake('public');

    [$supervisor, $club, $template] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();
    $otherEvent = Event::factory()->past()->create(['status' => 'active']);

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $template->id,
            'user_id' => $student->id,
            'event_id' => $otherEvent->id,
        ])
        ->assertSessionHasErrors('event_id');

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});

test('manual issuance rejects a template from another club', function () {
    Storage::fake('public');

    [$supervisor, $club] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();
    $otherTemplate = CertificateTemplate::factory()->active()->create();

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $otherTemplate->id,
            'user_id' => $student->id,
        ])
        ->assertSessionHasErrors('template_id');

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});

test('manual issuance requires a template', function () {
    Storage::fake('public');

    [$supervisor, $club] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();

    $this->actingAs($supervisor)
        ->post(route('certificates.store-manual', $club), ['user_id' => $student->id])
        ->assertSessionHasErrors('template_id');

    $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
});

test('student cannot manually issue certificates', function () {
    [$supervisor, $club, $template] = clubWithCertificateSupervisor();
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post(route('certificates.store-manual', $club), [
            'template_id' => $template->id,
            'user_id' => $student->id,
        ])
        ->assertForbidden();
});

test('guest cannot manually issue certificates', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $student = User::factory()->student()->create();

    $this->post(route('certificates.store-manual', $club), ['user_id' => $student->id])
        ->assertRedirect(route('login'));
});

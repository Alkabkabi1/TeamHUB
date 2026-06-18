<?php

use App\Enums\CertificateField;
use App\Models\CertificatePlaceholder;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        $this->withoutVite();
        $this->withUnencryptedCookies([
            'locale' => config('app.locale', 'ar'),
            'appearance' => 'light',
            'sidebar_state' => 'true',
        ]);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Give a club an active default certificate template (with a valid background
 * image and one placeholder) so certificates can be rendered from it.
 */
function giveClubDefaultTemplate(Club $club): CertificateTemplate
{
    $template = CertificateTemplate::factory()->default()->withImage()->create([
        'club_id' => $club->id,
    ]);

    CertificatePlaceholder::factory()
        ->binding(CertificateField::RecipientName)
        ->create(['certificate_template_id' => $template->id]);

    return $template;
}

/**
 * Create an approved supervisor membership for the given club and return the supervisor.
 */
function supervisorForClub(Club $club): User
{
    $supervisor = User::factory()->clubSupervisor()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    return $supervisor;
}

/**
 * @return array<string, mixed>
 */
function validJoinApplicationPayload(User $user, array $overrides = []): array
{
    return array_merge([
        'full_name' => $user->name,
        'university_email' => $user->email,
        'phone' => '0500000000',
        'level' => 'المستوى العاشر',
        'major' => 'هندسة البرمجيات',
        'skills' => 'برمجة وتصميم',
        'weekly_hours' => 4,
        'tools' => 'VS Code, Figma',
        'motivation' => 'أرغب في الانضمام لتطوير مهاراتي',
        'contribution' => 'المساهمة في الفعاليات التقنية',
    ], $overrides);
}

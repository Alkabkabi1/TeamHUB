<?php

use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\MembershipApprovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// ---------------------------------------------------------------------------
// preferredLocale() — Arabic by default, English only when explicitly chosen
// ---------------------------------------------------------------------------

test('preferred locale defaults to arabic when no choice has been saved', function () {
    expect(User::factory()->create(['locale' => null])->preferredLocale())->toBe('ar');
});

test('preferred locale returns the explicitly saved locale', function () {
    expect(User::factory()->create(['locale' => 'en'])->preferredLocale())->toBe('en');
    expect(User::factory()->create(['locale' => 'ar'])->preferredLocale())->toBe('ar');
});

// ---------------------------------------------------------------------------
// Persisting the choice
// ---------------------------------------------------------------------------

test('switching locale persists the choice for an authenticated user', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertPlainCookie('locale', 'en');

    expect($user->fresh()->locale)->toBe('en');
});

test('a guest can switch locale without an error', function () {
    $this->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertPlainCookie('locale', 'en');
});

// ---------------------------------------------------------------------------
// SetLocale honours the stored preference when there is no explicit cookie
// ---------------------------------------------------------------------------

test('a logged-in user with no cookie is served their stored locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    (new SetLocale)->handle($request, fn () => response('ok'));

    expect(app()->getLocale())->toBe('en');
});

test('a logged-in user with no cookie and no stored locale gets arabic', function () {
    $user = User::factory()->create(['locale' => null]);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    (new SetLocale)->handle($request, fn () => response('ok'));

    expect(app()->getLocale())->toBe('ar');
});

test('an explicit locale cookie still wins over the stored preference', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $request = Request::create('/');
    $request->cookies->set('locale', 'ar');
    $request->setUserResolver(fn () => $user);

    (new SetLocale)->handle($request, fn () => response('ok'));

    expect(app()->getLocale())->toBe('ar');
});

// ---------------------------------------------------------------------------
// The guarantee: emails render in the recipient's locale, not the request's
// ---------------------------------------------------------------------------

test('notification emails render in the recipient locale regardless of request locale', function () {
    config(['mail.default' => 'array']);
    app()->setLocale('en'); // e.g. an English-using supervisor triggers the action

    $workspace = Workspace::factory()->create(['name' => 'نادي الحاسبات']);
    $arabicUser = User::factory()->create(['locale' => null, 'email' => 'arabic@teamhub.test']);
    $englishUser = User::factory()->create(['locale' => 'en', 'email' => 'english@teamhub.test']);

    $arabicUser->notifyNow(new MembershipApprovedNotification($workspace));
    $englishUser->notifyNow(new MembershipApprovedNotification($workspace));

    $bodies = [];
    foreach (Mail::mailer('array')->getSymfonyTransport()->messages() as $message) {
        $email = $message->getOriginalMessage();
        $bodies[$email->getTo()[0]->getAddress()] = $email->getHtmlBody();
    }

    expect($bodies['arabic@teamhub.test'])->toContain('مبارك');
    expect($bodies['english@teamhub.test'])->toContain('Congratulations');
});

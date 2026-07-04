<?php

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\File;

test('user-facing source files do not contain legacy Ruwad or UQU branding', function () {
    $needles = [
        'Ruwad',
        'ruwad',
        'رواد',
        'ruwad-mark',
        'uqu-logo',
        'RuwadAuthLayout',
        '@uqu.edu.sa',
        'Umm Al-Qura',
        'جامعة أم القرى',
        'جامعة ام القرى',
    ];

    $roots = [
        resource_path('js'),
        resource_path('views'),
        resource_path('css'),
        lang_path(),
        app_path(),
        config_path(),
        database_path('seeders'),
        database_path('factories'),
    ];

    $violations = [];

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            $path = $file->getPathname();

            if (! preg_match('/\.(php|svelte|ts|css|blade\.php)$/', $path)) {
                continue;
            }

            $contents = File::get($path);

            foreach ($needles as $needle) {
                if (str_contains($contents, $needle)) {
                    $violations[] = "{$path}: {$needle}";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

test('env example documents local-first defaults', function () {
    $env = file_get_contents(base_path('.env.example'));

    expect($env)
        ->toContain('SESSION_DRIVER=file')
        ->toContain('QUEUE_CONNECTION=sync')
        ->toContain('CACHE_STORE=file')
        ->toContain('FILESYSTEM_DISK=local')
        ->toContain('MAIL_MAILER=log')
        ->toContain('DB_CONNECTION=sqlite')
        ->toContain('DB_DATABASE=database/database.sqlite');
});

test('mail theme defaults to teamhub', function () {
    expect(config('mail.markdown.theme'))->toBe('teamhub');
});

test('key pages render without legacy branding strings', function () {
    $club = Club::factory()->create(['status' => 'active']);
    $user = User::factory()->student()->create();

    $legacy = ['Ruwad', 'ruwad', 'رواد', 'ruwad-mark', 'uqu-logo', '@uqu.edu.sa', 'Repository', 'Documentation'];

    if (config('demo.quick_login')) {
        $this->get(route('login'))->assertRedirect(route('home'));
    } else {
        $login = $this->get(route('login'));
        $login->assertOk();
        foreach ($legacy as $needle) {
            expect($login->getContent() ?? '')->not->toContain($needle);
        }
    }

    $responses = [
        $this->get('/'),
        $this->actingAs($user)->get(route('clubs.show', $club)),
        $this->actingAs($user)->get(route('hub.dashboard')),
    ];

    foreach ($responses as $response) {
        $response->assertOk();
        $body = $response->getContent() ?? '';

        foreach ($legacy as $needle) {
            expect($body)->not->toContain($needle);
        }
    }
});

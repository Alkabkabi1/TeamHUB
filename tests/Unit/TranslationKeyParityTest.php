<?php

/**
 * @return array<string, mixed>
 */
function translationFileKeys(string $locale, string $filename): array
{
    $path = base_path("lang/{$locale}/{$filename}");

    expect($path)->toBeReadableFile();

    /** @var array<string, mixed> $translations */
    $translations = require $path;

    return $translations;
}

/**
 * @param  array<string, mixed>  $array
 * @return list<string>
 */
function flattenTranslationKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenTranslationKeys($value, $fullKey));
        } else {
            $keys[] = $fullKey;
        }
    }

    return $keys;
}

test('arabic and english lang files have matching filenames', function () {
    $arabicFiles = collect(glob(base_path('lang/ar/*.php')))
        ->map(fn (string $path) => basename($path))
        ->sort()
        ->values()
        ->all();

    $englishFiles = collect(glob(base_path('lang/en/*.php')))
        ->map(fn (string $path) => basename($path))
        ->sort()
        ->values()
        ->all();

    expect($englishFiles)->toBe($arabicFiles);
});

test('arabic and english translation keys match for each lang file', function () {
    $files = collect(glob(base_path('lang/ar/*.php')))
        ->map(fn (string $path) => basename($path))
        ->all();

    foreach ($files as $filename) {
        $arabicKeys = flattenTranslationKeys(translationFileKeys('ar', $filename));
        $englishKeys = flattenTranslationKeys(translationFileKeys('en', $filename));

        sort($arabicKeys);
        sort($englishKeys);

        expect($arabicKeys)->toBe($englishKeys, "Translation keys mismatch in {$filename}");
    }
});

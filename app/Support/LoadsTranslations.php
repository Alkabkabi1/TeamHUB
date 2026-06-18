<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LoadsTranslations
{
    /**
     * @return array<string, mixed>
     */
    public static function all(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        $loader = fn (): array => self::loadFromDisk($locale);

        if (app()->environment('local')) {
            return $loader();
        }

        return Cache::remember("translations.{$locale}", 3600, $loader);
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFromDisk(string $locale): array
    {
        $path = lang_path($locale);

        if (! is_dir($path)) {
            return [];
        }

        $translations = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getFilenameWithoutExtension();
            $translations[$group] = require $file->getPathname();
        }

        return $translations;
    }
}

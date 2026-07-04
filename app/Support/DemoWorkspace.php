<?php

namespace App\Support;

use App\Models\Club;

class DemoWorkspace
{
    public const DEFAULT_NAME = 'TeamHUB Demo';

    public static function defaultClub(): Club
    {
        return Club::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->first()
            ?? Club::query()->firstOrCreate(
                ['name' => self::DEFAULT_NAME],
                [
                    'category' => 'تقني',
                    'college' => 'كلية الحاسبات والمعلومات',
                    'status' => 'active',
                    'theme' => config('theme.brand'),
                ],
            );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function options(): array
    {
        $club = self::defaultClub();

        return Club::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->whenEmpty(fn () => collect([$club]))
            ->unique('id')
            ->map(fn (Club $item) => ['id' => $item->id, 'name' => $item->name])
            ->values()
            ->all();
    }
}

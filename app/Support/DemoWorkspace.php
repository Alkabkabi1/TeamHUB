<?php

namespace App\Support;

use App\Models\Workspace;

class DemoWorkspace
{
    public const DEFAULT_NAME = 'TeamHUB Demo';

    public static function defaultWorkspace(): Workspace
    {
        return Workspace::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->first()
            ?? Workspace::query()->firstOrCreate(
                ['name' => self::DEFAULT_NAME],
                [
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
        $workspace = self::defaultWorkspace();

        return Workspace::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->whenEmpty(fn () => collect([$workspace]))
            ->unique('id')
            ->map(fn (Workspace $item) => ['id' => $item->id, 'name' => $item->name])
            ->values()
            ->all();
    }
}

<?php

namespace App\Filament\Resources\Clubs\RelationManagers;

use App\Filament\Resources\Clubs\Resources\Events\EventResource;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

/**
 * Surfaces the nested Event resource on the club's page. Events are "heavy"
 * (they own attendances), so they are a nested resource with their own pages
 * and relation managers rather than a flat relation manager.
 */
class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $relatedResource = EventResource::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.events');
    }
}

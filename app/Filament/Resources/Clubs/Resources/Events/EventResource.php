<?php

namespace App\Filament\Resources\Clubs\Resources\Events;

use App\Filament\Resources\Clubs\ClubResource;
use App\Filament\Resources\Clubs\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Clubs\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Clubs\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Clubs\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $parentResource = ClubResource::class;

    public static function getModelLabel(): string
    {
        return __('admin.nav.event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.nav.events');
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}

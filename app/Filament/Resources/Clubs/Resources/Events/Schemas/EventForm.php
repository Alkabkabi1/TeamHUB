<?php

namespace App\Filament\Resources\Clubs\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Models\Event;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('events.form.field_title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('events.form.field_description'))
                    ->columnSpanFull(),
                DateTimePicker::make('starts_at')
                    ->label(__('events.form.field_starts_at'))
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label(__('events.form.field_ends_at'))
                    ->required()
                    ->after('starts_at'),
                TextInput::make('location')
                    ->label(__('events.form.field_location'))
                    ->maxLength(255),
                TextInput::make('capacity')
                    ->label(__('events.form.field_capacity'))
                    ->numeric()
                    ->minValue(1),
                Select::make('status')
                    ->label(__('events.form.field_status'))
                    ->options(EventStatus::class)
                    ->default(EventStatus::Active->value)
                    ->required(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->label(__('events.form.field_images'))
                    ->collection(Event::IMAGE_COLLECTION)
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->image()
                    ->maxFiles(10)
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ]);
    }
}

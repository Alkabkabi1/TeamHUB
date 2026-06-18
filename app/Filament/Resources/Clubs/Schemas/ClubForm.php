<?php

namespace App\Filament\Resources\Clubs\Schemas;

use App\Enums\ClubStatus;
use App\Models\Club;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClubForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.clubs.form.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('category')
                    ->label(__('admin.clubs.form.category'))
                    ->maxLength(255),
                TextInput::make('college')
                    ->label(__('admin.clubs.form.college'))
                    ->maxLength(255),
                Select::make('tags')
                    ->label(__('admin.clubs.form.tags'))
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique('tags', 'name'),
                    ]),
                Select::make('status')
                    ->label(__('admin.clubs.form.status'))
                    ->options(ClubStatus::class)
                    ->default(ClubStatus::Active->value)
                    ->required(),
                ColorPicker::make('theme')
                    ->label(__('admin.clubs.form.theme')),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label(__('admin.clubs.form.logo'))
                    ->collection(Club::LOGO_COLLECTION)
                    ->image()
                    ->avatar()
                    ->maxSize(5120),
            ]);
    }
}

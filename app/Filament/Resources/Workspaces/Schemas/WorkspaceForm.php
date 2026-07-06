<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use App\Enums\WorkspaceStatus;
use App\Models\Workspace;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkspaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.workspaces.form.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->label(__('admin.workspaces.form.status'))
                    ->options(WorkspaceStatus::class)
                    ->default(WorkspaceStatus::Active->value)
                    ->required(),
                ColorPicker::make('theme')
                    ->label(__('admin.workspaces.form.theme')),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label(__('admin.workspaces.form.logo'))
                    ->collection(Workspace::LOGO_COLLECTION)
                    ->image()
                    ->avatar()
                    ->maxSize(5120),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return 'Projects';
    }

    public static function getModelLabel(): string
    {
        return 'Project';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Projects';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('workspace');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->maxLength(2000)
                ->columnSpanFull(),
            TextInput::make('theme')
                ->label('Brand color')
                ->placeholder('#c8924a'),
            Select::make('status')
                ->options(array_combine(ProjectStatus::values(), ProjectStatus::values()))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workspace.name')
                    ->label('Workspace')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(ProjectStatus::values(), ProjectStatus::values())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}

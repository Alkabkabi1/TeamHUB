<?php

namespace App\Filament\Resources\Clubs\RelationManagers;

use App\Models\ClubResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.resources');
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            ClubResource::TYPE_DOWNLOAD => 'Download',
            ClubResource::TYPE_MEDIA => 'Media',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('admin.resources_fields.title'))
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label(__('admin.resources_fields.type'))
                    ->options(self::typeOptions())
                    ->default(ClubResource::TYPE_DOWNLOAD)
                    ->required(),
                Textarea::make('description')
                    ->label(__('admin.resources_fields.description'))
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label(__('admin.resources_fields.file'))
                    ->disk('public')
                    ->directory('club-resources')
                    ->maxSize(20480),
                DateTimePicker::make('published_at')
                    ->label(__('admin.resources_fields.published_at'))
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.resources_fields.title'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.resources_fields.type'))
                    ->badge(),
                TextColumn::make('published_at')
                    ->label(__('admin.resources_fields.published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(self::typeOptions()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

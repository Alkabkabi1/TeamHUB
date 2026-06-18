<?php

namespace App\Filament\Resources\Clubs\RelationManagers;

use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.news');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('news.form.field_title'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label(__('news.form.field_body'))
                    ->required()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->label(__('news.form.image'))
                    ->collection(Post::IMAGE_COLLECTION)
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->image()
                    ->maxFiles(10)
                    ->maxSize(10240)
                    ->columnSpanFull(),
                DateTimePicker::make('published_at')
                    ->label(__('news.form.field_published_at'))
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label(__('news.form.image'))
                    ->collection(Post::IMAGE_COLLECTION)
                    ->circular()
                    ->limit(3)
                    ->stacked(),
                TextColumn::make('title')
                    ->label(__('news.form.field_title'))
                    ->searchable(),
                TextColumn::make('author.name')
                    ->label(__('news.form.field_author'))
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label(__('news.form.field_published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] ??= Auth::id();

                        return $data;
                    }),
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

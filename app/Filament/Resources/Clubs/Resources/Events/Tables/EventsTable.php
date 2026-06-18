<?php

namespace App\Filament\Resources\Clubs\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('events.form.field_title'))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label(__('events.form.field_starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('events.form.field_status'))
                    ->badge(),
                TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label(__('admin.relations.members'))
                    ->badge(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

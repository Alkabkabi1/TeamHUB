<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Enums\WorkspaceStatus;
use App\Models\Workspace;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label(__('admin.clubs.columns.logo'))
                    ->collection(Workspace::LOGO_COLLECTION)
                    ->circular(),
                TextColumn::make('name')
                    ->label(__('admin.clubs.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.clubs.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('memberships_count')
                    ->counts('memberships')
                    ->label(__('admin.clubs.columns.members'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.clubs.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(WorkspaceStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

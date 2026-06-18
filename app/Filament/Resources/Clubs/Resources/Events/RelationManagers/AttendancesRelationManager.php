<?php

namespace App\Filament\Resources\Clubs\Resources\Events\RelationManagers;

use App\Enums\EventAttendanceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(EventAttendanceStatus::cases())
            ->mapWithKeys(fn (EventAttendanceStatus $status): array => [$status->value => ucfirst(str_replace('_', ' ', $status->value))])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(self::statusOptions())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.members.member'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('checked_in_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
            ])
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

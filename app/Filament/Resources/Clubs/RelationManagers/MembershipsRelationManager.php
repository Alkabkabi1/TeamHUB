<?php

namespace App\Filament\Resources\Clubs\RelationManagers;

use App\Enums\ClubRole;
use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\ClubMembership;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.members');
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        return collect(ClubRole::cases())
            ->mapWithKeys(fn (ClubRole $role): array => [$role->value => __($role->label())])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Only shown when adding a member; on edit the user is fixed.
                Select::make('user_id')
                    ->label(__('admin.members.member'))
                    ->options(function (RelationManager $livewire): array {
                        $existing = $livewire->getOwnerRecord()->memberships()->pluck('user_id');

                        return User::query()
                            ->where('role', UserRole::Student->value)
                            ->whereNotIn('id', $existing)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->required()
                    ->visibleOn('create'),
                Select::make('status')
                    ->label(__('admin.members.status'))
                    ->options(MembershipStatus::class)
                    ->default(MembershipStatus::Approved->value)
                    ->required(),
                CheckboxList::make('club_roles')
                    ->label(__('admin.members.roles'))
                    ->options(self::roleOptions())
                    ->default([ClubRole::Member->value])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->emptyStateHeading(__('members.no_members'))
            ->emptyStateDescription(__('members.empty_hint'))
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.members.member'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.role')
                    ->label(__('admin.members.roles'))
                    ->badge()
                    ->formatStateUsing(fn (ClubRole $state): string => __($state->label())),
                TextColumn::make('status')
                    ->label(__('admin.members.status'))
                    ->badge()
                    // `status` is a plain string column, so map it to the enum's
                    // localized label for display.
                    ->formatStateUsing(fn (string $state): string => MembershipStatus::from($state)->getLabel()),
                TextColumn::make('joined_at')
                    ->label(__('admin.members.joined_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MembershipStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('members.add_member'))
                    ->modalHeading(__('members.add_member'))
                    ->using(function (array $data, RelationManager $livewire): ClubMembership {
                        $membership = ClubMembership::create([
                            'user_id' => $data['user_id'],
                            'club_id' => $livewire->getOwnerRecord()->getKey(),
                            'status' => $data['status'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'joined_at' => now(),
                        ]);

                        $membership->syncClubRoles(self::resolveRoles($data['club_roles'] ?? []));

                        return $membership;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(__('members.edit_member'))
                    ->fillForm(fn (ClubMembership $record): array => [
                        'status' => $record->status,
                        'club_roles' => $record->clubRoles()->map(fn (ClubRole $role): string => $role->value)->all(),
                    ])
                    ->action(function (ClubMembership $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                        $record->syncClubRoles(self::resolveRoles($data['club_roles'] ?? []));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Resolve submitted role values into ClubRole enums, always keeping the
     * baseline Member role so every member carries it.
     *
     * @param  array<int, string>  $values
     * @return array<int, ClubRole>
     */
    private static function resolveRoles(array $values): array
    {
        $roles = array_map(fn (string $value): ClubRole => ClubRole::from($value), $values);

        if (! in_array(ClubRole::Member, $roles, true)) {
            $roles[] = ClubRole::Member;
        }

        return $roles;
    }
}

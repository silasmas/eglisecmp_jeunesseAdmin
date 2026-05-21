<?php

namespace App\Filament\Resources\Users\Tables;

use Filafly\IdentityColumn\Tables\Columns\IdentityColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IdentityColumn::make('name')
                    ->label('Utilisateur')
                    ->avatar(fn ($record): string => Filament::getUserAvatarUrl($record))
                    ->primary('name')
                    ->secondary('email')
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->sticky(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                SelectColumn::make('primary_role_id')
                    ->label('Role principal')
                    ->getStateUsing(fn ($record): ?int => $record->roles->first()?->id)
                    ->options(fn (): array => Role::query()
                        ->where('guard_name', 'web')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->updateStateUsing(function ($record, $state): ?int {
                        $role = Role::query()->whereKey($state)->where('guard_name', 'web')->first();

                        if (! $role) {
                            return $record->roles->first()?->id;
                        }

                        $record->syncRoles([$role->name]);
                        $record->load('roles');

                        return (int) $role->id;
                    })
                    ->selectablePlaceholder(false),
                TextColumn::make('fonction_metier')
                    ->label('Fonction metier')
                    ->searchable(),
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('postnom')
                    ->label('Post-nom')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('prenom')
                    ->label('Prenom')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sexe')
                    ->label('Sexe')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_naissance')
                    ->label('Date naissance')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telephone')
                    ->label('Telephone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('telephone_urgence')
                    ->label('Tel. urgence')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guardian_name')
                    ->label('Parent / tuteur')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guardian_phone')
                    ->label('Tel. tuteur')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('adresse')
                    ->label('Adresse')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commune')
                    ->label('Commune')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('eglise_assemblee')
                    ->label('Eglise / Assemblee')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('departement_cellule')
                    ->label('Departement / Cellule')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hebergement_choice')
                    ->label('Hebergement')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role_jeunesse')
                    ->label('Role jeunesse')
                    ->badge()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('last_login')
                    ->label('Derniere connexion')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis a jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

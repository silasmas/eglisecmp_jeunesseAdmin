<?php

namespace App\Filament\Resources\RetreatPayments\Tables;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use App\Filament\Support\RetreatPaymentFlexPayFilamentActions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use TinusG\FilamentHoverImageColumn\HoverImageColumn;
use Wezlo\FilamentRecordWatcher\Actions\UnwatchAction;
use Wezlo\FilamentRecordWatcher\Actions\WatchAction;
use Zvizvi\UserFields\Components\UserColumn;

class RetreatPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'participant',
                'event',
                'accessGrantedBy',
            ]))
            ->columns([
                HoverImageColumn::make('participant.photo')
                    ->label('Profil')
                    ->previewSize(320)
                    ->defaultImageUrl(fn ($record): string => self::avatarUrl((string) $record->participant?->full_name))
                    ->sticky(),
                TextColumn::make('participant.nom')
                    ->label('Participant')
                    ->formatStateUsing(fn ($record): string => $record->participant?->full_name ?? 'Non defini')
                    ->searchable(['nom', 'prenom'])
                    ->sticky(),
                TextColumn::make('event.name')
                    ->label('Evenement')
                    ->searchable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sticky(),
                TextColumn::make('amount_expected')
                    ->label('Montant attendu')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Montant recu')
                    ->state(fn ($record): float => $record->resolveReceivedAmount())
                    ->numeric(decimalPlaces: 2)
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw(
                            'CASE WHEN channel = ? AND etat = ? AND amount_paid <= 0 THEN amount_expected ELSE amount_paid END '.$direction,
                            ['cash', 'payee']
                        );
                    }),
                TextColumn::make('currency')
                    ->label('Devise')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Telephone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider_reference')
                    ->label('Reference provider')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider_status_code')
                    ->label('Code provider')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider_message')
                    ->label('Message provider')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('etat')
                    ->label('Etat')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('access_granted')
                    ->label('Acces autorise')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('access_granted_at')
                    ->label("Date d'autorisation")
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('accessGrantedBy')
                    ->label('Autorise par')
                    ->wrapped()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Date paiement')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('etat')
                    ->label('État')
                    ->options([
                        'init' => 'Initialisé',
                        'en_cours' => 'En attente',
                        'echouee' => 'Échoué',
                        'annulee' => 'Annulé',
                        'payee' => 'Payé',
                        'remboursee' => 'Remboursé',
                    ]),
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'card' => 'Carte',
                        'cash' => 'Espèces',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    RetreatPaymentFlexPayFilamentActions::recheckAction(),
                    RetreatPaymentFlexPayFilamentActions::relaunchAction(),
                    WatchAction::make(),
                    UnwatchAction::make(),
                    Action::make('open_in_new_tab')
                        ->label('Ouvrir dans un onglet')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record): string => RetreatPaymentResource::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                ])
                    ->iconButton()
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function avatarUrl(string $name): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=7b1d3e&color=fff';
    }
}

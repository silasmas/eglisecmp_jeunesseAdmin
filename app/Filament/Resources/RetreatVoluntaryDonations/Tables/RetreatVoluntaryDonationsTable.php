<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations\Tables;

use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use App\Models\RetreatVoluntaryDonation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Table Filament des dons volontaires.
 */
class RetreatVoluntaryDonationsTable
{
    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['event']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('donor_name')
                    ->label('Donateur')
                    ->searchable(),
                TextColumn::make('donation_kind')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        RetreatVoluntaryDonation::KIND_IN_KIND => 'Nature',
                        RetreatVoluntaryDonation::KIND_CASH => 'Espèces',
                        default => $state,
                    }),
                TextColumn::make('cash_purpose')
                    ->label('Destination')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        RetreatVoluntaryDonation::PURPOSE_GENERAL => 'Fonctionnement',
                        RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH => 'Sponsor jeunes',
                        default => '—',
                    })
                    ->toggleable(),
                TextColumn::make('amount_expected')
                    ->label('Montant attendu')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Montant payé')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Devise')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RetreatVoluntaryDonation::STATUS_PAID => 'success',
                        RetreatVoluntaryDonation::STATUS_SUBMITTED => 'info',
                        RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED => 'warning',
                        RetreatVoluntaryDonation::STATUS_PENDING => 'warning',
                        RetreatVoluntaryDonation::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('admin_notified')
                    ->label('Admin notifié')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event.name')
                    ->label('Événement')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('donation_kind')
                    ->label('Type')
                    ->options([
                        RetreatVoluntaryDonation::KIND_IN_KIND => 'Nature',
                        RetreatVoluntaryDonation::KIND_CASH => 'Espèces',
                    ]),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        RetreatVoluntaryDonation::STATUS_SUBMITTED => 'Soumis',
                        RetreatVoluntaryDonation::STATUS_PENDING => 'En attente paiement',
                        RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED => 'Cash à valider',
                        RetreatVoluntaryDonation::STATUS_PAID => 'Payé',
                        RetreatVoluntaryDonation::STATUS_CANCELLED => 'Annulé',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

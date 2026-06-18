<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations\Schemas;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatSponsorshipVoucher;
use App\Models\RetreatVoluntaryDonation;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Fiche détail d'un don volontaire (infolist Filament).
 */
class RetreatVoluntaryDonationInfolist
{
    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Donateur')
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable()
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('donor_name')->label('Nom'),
                        TextEntry::make('donor_phone')->label('Téléphone contact')->placeholder('—'),
                        TextEntry::make('donor_email')->label('E-mail')->placeholder('—'),
                        TextEntry::make('event.name')->label('Événement'),
                    ])
                    ->columns(2),

                Section::make('Don')
                    ->schema([
                        TextEntry::make('donation_kind')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                RetreatVoluntaryDonation::KIND_IN_KIND => 'Nature',
                                RetreatVoluntaryDonation::KIND_CASH => 'Espèces',
                                default => $state,
                            }),
                        TextEntry::make('cash_purpose')
                            ->label('Destination')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                RetreatVoluntaryDonation::PURPOSE_GENERAL => 'Bon fonctionnement',
                                RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH => 'Prise en charge jeunes',
                                default => '—',
                            })
                            ->visible(fn (RetreatVoluntaryDonation $record): bool => $record->donation_kind === RetreatVoluntaryDonation::KIND_CASH),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                RetreatVoluntaryDonation::STATUS_PAID => 'success',
                                RetreatVoluntaryDonation::STATUS_SUBMITTED => 'info',
                                RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED => 'warning',
                                RetreatVoluntaryDonation::STATUS_PENDING => 'warning',
                                RetreatVoluntaryDonation::STATUS_CANCELLED => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                RetreatVoluntaryDonation::STATUS_PAID => 'Payé',
                                RetreatVoluntaryDonation::STATUS_SUBMITTED => 'Soumis',
                                RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED => 'Cash à valider',
                                RetreatVoluntaryDonation::STATUS_PENDING => 'En attente paiement',
                                RetreatVoluntaryDonation::STATUS_CANCELLED => 'Annulé',
                                default => $state,
                            }),
                        TextEntry::make('amount_expected')
                            ->label('Montant attendu')
                            ->money(fn (RetreatVoluntaryDonation $record): string => (string) $record->currency),
                        TextEntry::make('amount_paid')
                            ->label('Montant payé')
                            ->money(fn (RetreatVoluntaryDonation $record): string => (string) $record->currency),
                        TextEntry::make('in_kind_description')
                            ->label('Description (nature)')
                            ->columnSpanFull()
                            ->visible(fn (RetreatVoluntaryDonation $record): bool => $record->donation_kind === RetreatVoluntaryDonation::KIND_IN_KIND),
                        TextEntry::make('donor_message')
                            ->label('Message du donateur')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Paiement')
                    ->schema([
                        TextEntry::make('paymentDetailsSummary')
                            ->label('Résumé')
                            ->state(fn (RetreatVoluntaryDonation $record): string => $record->paymentDetailsSummary())
                            ->columnSpanFull(),
                        TextEntry::make('payment_channel')
                            ->label('Canal')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'mobile_money' => 'Mobile Money',
                                'card' => 'Carte bancaire',
                                'cash' => 'Espèces',
                                default => $state ?? '—',
                            }),
                        TextEntry::make('payment_operator')
                            ->label('Opérateur / moyen')
                            ->placeholder('—'),
                        TextEntry::make('payment_phone')
                            ->label('Numéro Mobile Money')
                            ->placeholder('—')
                            ->visible(fn (RetreatVoluntaryDonation $record): bool => $record->payment_channel === 'mobile_money'),
                        TextEntry::make('provider_reference')
                            ->label('Réf. opérateur / passerelle')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->visible(fn (RetreatVoluntaryDonation $record): bool => $record->donation_kind === RetreatVoluntaryDonation::KIND_CASH),

                Section::make('Prise en charge jeunes')
                    ->description('Codes parrainage à transmettre aux jeunes pour leur inscription.')
                    ->schema([
                        TextEntry::make('youth_slots_count')
                            ->label('Jeunes à prendre en charge'),
                        TextEntry::make('sponsorshipSlotsUsed')
                            ->label('Inscrits via code')
                            ->state(fn (RetreatVoluntaryDonation $record): int => $record->sponsorshipSlotsUsed())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('sponsorshipSlotsRemaining')
                            ->label('Places restantes')
                            ->state(fn (RetreatVoluntaryDonation $record): int => $record->sponsorshipSlotsRemaining())
                            ->badge()
                            ->color(fn (RetreatVoluntaryDonation $record): string => $record->sponsorshipSlotsRemaining() > 0 ? 'warning' : 'gray'),
                        TextEntry::make('sponsorshipProgressLabel')
                            ->label('Progression')
                            ->state(fn (RetreatVoluntaryDonation $record): string => $record->sponsorshipProgressLabel())
                            ->badge()
                            ->color('info')
                            ->columnSpanFull(),
                        RepeatableEntry::make('vouchers')
                            ->label('Codes parrainage')
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Code')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('Code copié')
                                    ->copyMessageDuration(2000),
                                TextEntry::make('voucher_status')
                                    ->label('Statut')
                                    ->state(fn (RetreatSponsorshipVoucher $record): string => $record->uses_remaining > 0 ? 'Disponible' : 'Utilisé')
                                    ->badge()
                                    ->color(fn (RetreatSponsorshipVoucher $record): string => $record->uses_remaining > 0 ? 'warning' : 'success'),
                                TextEntry::make('redeemed_at')
                                    ->label('Utilisé le')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—'),
                                TextEntry::make('redeemed_participant')
                                    ->label('Jeune inscrit')
                                    ->state(function (RetreatSponsorshipVoucher $record): string {
                                        $participant = $record->redeemedByParticipant;

                                        return $participant
                                            ? trim($participant->prenom.' '.$participant->nom)
                                            : '—';
                                    })
                                    ->url(function (RetreatSponsorshipVoucher $record): ?string {
                                        if (! $record->redeemed_by_participant_id) {
                                            return null;
                                        }

                                        return RetreatParticipantResource::getUrl('view', [
                                            'record' => $record->redeemed_by_participant_id,
                                        ]);
                                    })
                                    ->color('primary')
                                    ->placeholder('—'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn (RetreatVoluntaryDonation $record): bool => $record->cash_purpose === RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH),
            ]);
    }
}

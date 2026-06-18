<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/**
 * Fiche détail d'un don volontaire.
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
                TextEntry::make('reference')->label('Référence'),
                TextEntry::make('donor_name')->label('Donateur'),
                TextEntry::make('donor_phone')->label('Téléphone'),
                TextEntry::make('donor_email')->label('E-mail'),
                TextEntry::make('donation_kind')->label('Type de don'),
                TextEntry::make('cash_purpose')->label('Destination espèces'),
                TextEntry::make('in_kind_description')
                    ->label('Description (nature)')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record->donation_kind === 'in_kind'),
                TextEntry::make('youth_slots_count')->label('Places sponsorisées'),
                TextEntry::make('amount_expected')->label('Montant attendu'),
                TextEntry::make('amount_paid')->label('Montant payé'),
                TextEntry::make('currency')->label('Devise'),
                TextEntry::make('status')->label('Statut'),
                TextEntry::make('payment_channel')->label('Canal paiement'),
                TextEntry::make('provider_reference')->label('Réf. opérateur'),
                TextEntry::make('donor_message')
                    ->label('Message')
                    ->columnSpanFull(),
                TextEntry::make('event.name')->label('Événement'),
                RepeatableEntry::make('vouchers')
                    ->label('Codes parrainage générés')
                    ->schema([
                        TextEntry::make('code')->label('Code'),
                        TextEntry::make('status')->label('Statut'),
                        TextEntry::make('redeemed_at')->label('Utilisé le')->dateTime('d/m/Y H:i'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

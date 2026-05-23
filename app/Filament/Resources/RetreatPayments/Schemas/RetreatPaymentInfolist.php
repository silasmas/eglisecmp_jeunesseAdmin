<?php

namespace App\Filament\Resources\RetreatPayments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->schema([
                        TextEntry::make('participant.id')
                            ->label('Participant'),
                        TextEntry::make('event.name')
                            ->label('Evenement'),
                        TextEntry::make('reference')
                            ->label('Reference'),
                        TextEntry::make('etat')
                            ->label('Etat')
                            ->badge(),
                    ])
                    ->columns(2),
                Section::make('Montants')
                    ->schema([
                        TextEntry::make('amount_expected')
                            ->label('Montant attendu')
                            ->numeric(),
                        TextEntry::make('amount_paid')
                            ->label('Montant recu')
                            ->numeric(),
                        TextEntry::make('currency')
                            ->label('Devise'),
                        TextEntry::make('channel')
                            ->label('Canal'),
                        TextEntry::make('phone')
                            ->label('Telephone')
                            ->placeholder('-'),
                        TextEntry::make('paid_at')
                            ->label('Date paiement')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Provider et acces')
                    ->schema([
                        TextEntry::make('provider_reference')
                            ->label('Reference provider')
                            ->placeholder('-'),
                        TextEntry::make('provider_status_code')
                            ->label('Code provider')
                            ->placeholder('-'),
                        TextEntry::make('provider_message')
                            ->label('Message provider')
                            ->placeholder('-'),
                        IconEntry::make('access_granted')
                            ->label('Acces autorise')
                            ->boolean(),
                        TextEntry::make('access_granted_at')
                            ->label("Date d'autorisation")
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('accessGrantedBy.name')
                            ->label('Autorise par')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}

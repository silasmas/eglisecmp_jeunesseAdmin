<?php

namespace App\Filament\Resources\RetreatPayments\Schemas;

use App\Models\RetreatPayment;
use App\Support\RetreatInscriptionResumeUrl;
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
                        TextEntry::make('resume_payment_link')
                            ->label('Lien reprise inscription')
                            ->placeholder('—')
                            ->copyable()
                            ->copyMessage('Lien copié')
                            ->copyableState(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->state(fn (RetreatPayment $record): ?string => RetreatInscriptionResumeUrl::urlForPayment($record))
                            ->url(fn (RetreatPayment $record): ?string => RetreatInscriptionResumeUrl::urlForPayment($record))
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->wrap()
                            ->columnSpanFull()
                            ->visible(fn (RetreatPayment $record): bool => RetreatInscriptionResumeUrl::canResumeForPayment($record)),
                    ])
                    ->columns(2),
                Section::make('Montants')
                    ->schema([
                        TextEntry::make('amount_expected')
                            ->label('Montant attendu')
                            ->numeric(),
                        TextEntry::make('amount_paid')
                            ->label('Montant recu')
                            ->formatStateUsing(fn ($state, $record): string => number_format($record->resolveReceivedAmount(), 2, ',', ' ')),
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

<?php

namespace App\Filament\Resources\RetreatParticipants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatParticipantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identite')
                    ->schema([
                        TextEntry::make('nom'),
                        TextEntry::make('prenom'),
                        TextEntry::make('age')->numeric(),
                        TextEntry::make('email')->label('Email')->placeholder('-'),
                        TextEntry::make('sexe')->placeholder('-'),
                        TextEntry::make('telephone')->placeholder('-'),
                        TextEntry::make('adresse')->placeholder('-'),
                        TextEntry::make('telephone_urgence')->label('Tel urgence')->placeholder('-'),
                        TextEntry::make('photo')->placeholder('-'),
                        TextEntry::make('observation')->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Rattachement')
                    ->schema([
                        TextEntry::make('user.name')->label('Utilisateur')->placeholder('-'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('-'),
                        TextEntry::make('atelier_id')->numeric()->placeholder('-'),
                        TextEntry::make('chambre_id')->numeric()->placeholder('-'),
                        TextEntry::make('role_participant'),
                        TextEntry::make('participant_type'),
                        TextEntry::make('qr_code')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Paiement et billet')
                    ->schema([
                        TextEntry::make('preuve_paiement')->placeholder('-'),
                        IconEntry::make('paiement_valide')->boolean(),
                        IconEntry::make('billet_envoye')->boolean(),
                        TextEntry::make('date_billet_envoye')->dateTime()->placeholder('-'),
                        TextEntry::make('billet_pdf')->placeholder('-'),
                        IconEntry::make('billet_envoye_email')->boolean(),
                        IconEntry::make('billet_envoye_whatsapp')->boolean(),
                        IconEntry::make('badge_received')
                            ->label('Badge remis')
                            ->boolean()
                            ->visible(fn ($record): bool => (bool) $record->paiement_valide),
                        TextEntry::make('badge_received_at')
                            ->label('Badge remis le')
                            ->dateTime()
                            ->placeholder('En attente de remise')
                            ->visible(fn ($record): bool => (bool) $record->paiement_valide),
                    ])
                    ->columns(2),
                Section::make('Presence et inscription')
                    ->schema([
                        IconEntry::make('present')->boolean(),
                        TextEntry::make('date_presence')->dateTime()->placeholder('-'),
                        IconEntry::make('exit_allowed')->boolean(),
                        TextEntry::make('curfew_time')->time()->placeholder('-'),
                        TextEntry::make('guardian_name')->placeholder('-'),
                        TextEntry::make('guardian_phone')->placeholder('-'),
                        TextEntry::make('registration_status'),
                        TextEntry::make('registration_otp_code')->placeholder('-'),
                        TextEntry::make('registration_otp_sent_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_expires_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_verified_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_attempts')->numeric(),
                        IconEntry::make('is_verified')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}

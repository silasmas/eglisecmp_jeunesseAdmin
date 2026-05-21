<?php

namespace App\Filament\Resources\ChurchEvents\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Slimani\MediaManager\Infolists\Components\MediaImageEntry;

class ChurchEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vue generale')
                    ->schema([
                        MediaImageEntry::make('afficheMedia')
                            ->label('Affiche')
                            ->conversion('thumb'),
                        TextEntry::make('name')->label('Nom'),
                        TextEntry::make('type')->label("Type d'evenement"),
                        TextEntry::make('location')->label('Lieu'),
                        IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Dates et capacite')
                    ->schema([
                        TextEntry::make('start_at')
                            ->label('Debut')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('end_at')
                            ->label('Fin')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('capacity')
                            ->label('Capacite')
                            ->numeric()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Acces et paiement')
                    ->schema([
                        TextEntry::make('price_to_pay')
                            ->label('Montant a payer')
                            ->numeric(),
                        TextEntry::make('currency')->label('Devise'),
                        TextEntry::make('access_auth_mode')
                            ->label("Mode d'authentification")
                            ->badge(),
                        TextEntry::make('access_otp_channel')
                            ->label('Canal OTP')
                            ->badge()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}

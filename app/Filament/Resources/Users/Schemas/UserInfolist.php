<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil')
                    ->schema([
                        ImageEntry::make('profile_photo_path')
                            ->label('Photo profil')
                            ->circular()
                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode((string) $record->name).'&background=7b1d3e&color=fff')
                            ->columnSpanFull(),
                        TextEntry::make('initiales')
                            ->label('Initiales')
                            ->getStateUsing(function ($record): string {
                                $parts = preg_split('/\s+/', trim((string) $record->name)) ?: [];
                                $initials = collect($parts)->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode('');

                                return mb_strtoupper($initials);
                            }),
                        TextEntry::make('name')
                            ->label('Nom'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('fonction_metier')
                            ->label('Fonction metier')
                            ->placeholder('-'),
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->separator(','),
                        IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                    ])
                    ->columns(2),
                Section::make('Securite et suivi')
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label('Email verifie le')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('last_login')
                            ->label('Derniere connexion')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('owner.name')
                            ->label('Responsable parent')
                            ->placeholder('-'),
                        IconEntry::make('chambres_responsable_exists')
                            ->label('Responsable de chambre')
                            ->getStateUsing(fn ($record): bool => $record->chambresResponsable()->exists())
                            ->boolean(),
                        IconEntry::make('ateliers_responsable_exists')
                            ->label("Responsable d'atelier")
                            ->getStateUsing(fn ($record): bool => $record->ateliersResponsable()->exists())
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Cree le')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Mis a jour le')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}

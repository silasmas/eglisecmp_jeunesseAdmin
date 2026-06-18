<?php

namespace App\Filament\Resources\GeneratedQrCodes\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use App\Support\QrCodeLogoCatalog;

/**
 * Vue détail d'un QR code généré.
 */
class GeneratedQrCodeInfolist
{
    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Titre'),
                TextEntry::make('target_url')
                    ->label('Lien cible')
                    ->copyable()
                    ->columnSpanFull(),
                IconEntry::make('embed_logo')
                    ->label('Logo au centre')
                    ->boolean(),
                TextEntry::make('logo_key')
                    ->label('Logo choisi')
                    ->formatStateUsing(fn (?string $state): string => QrCodeLogoCatalog::selectOptions()[$state ?? ''] ?? ($state ?? '—'))
                    ->visible(fn ($record): bool => (bool) $record?->embed_logo),
                ImageEntry::make('file_path')
                    ->label('Aperçu')
                    ->disk('public')
                    ->height(320)
                    ->visible(fn ($record): bool => filled($record?->file_path))
                    ->columnSpanFull(),
                TextEntry::make('creator.name')
                    ->label('Créé par')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Créé le')
                    ->dateTime(),
            ]);
    }
}

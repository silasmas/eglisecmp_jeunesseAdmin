<?php

namespace App\Filament\Resources\GeneratedQrCodes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Support\QrCodeLogoCatalog;
use Illuminate\Support\Facades\Storage;

/**
 * Liste des QR codes générés.
 */
class GeneratedQrCodesTable
{
    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_path')
                    ->label('QR')
                    ->disk('public')
                    ->height(48)
                    ->square(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_url')
                    ->label('Lien')
                    ->limit(50)
                    ->tooltip(fn ($record): string => (string) $record->target_url)
                    ->searchable(),
                IconColumn::make('embed_logo')
                    ->label('Logo')
                    ->boolean(),
                TextColumn::make('logo_key')
                    ->label('Variante')
                    ->formatStateUsing(fn (?string $state): string => QrCodeLogoCatalog::selectOptions()[$state ?? ''] ?? '—')
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download')
                    ->label('Télécharger PNG')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn ($record): bool => filled($record->file_path))
                    ->action(function ($record) {
                        $path = Storage::disk('public')->path((string) $record->file_path);
                        $filename = 'qr-'.str($record->title)->slug().'.png';

                        return response()->download($path, $filename);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

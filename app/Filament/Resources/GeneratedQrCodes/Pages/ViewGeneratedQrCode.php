<?php

namespace App\Filament\Resources\GeneratedQrCodes\Pages;

use App\Filament\Resources\GeneratedQrCodes\GeneratedQrCodeResource;
use App\Services\QrCode\QrCodeGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

/**
 * Détail d'un QR code avec téléchargement et régénération.
 */
class ViewGeneratedQrCode extends ViewRecord
{
    protected static string $resource = GeneratedQrCodeResource::class;

    /**
     * @return array<int, Action|EditAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Télécharger PNG')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => filled($this->record->file_path))
                ->action(function () {
                    $path = Storage::disk('public')->path((string) $this->record->file_path);
                    $filename = 'qr-'.str($this->record->title)->slug().'.png';

                    return response()->download($path, $filename);
                }),
            Action::make('regenerate')
                ->label('Régénérer')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    app(QrCodeGeneratorService::class)->generateAndStore($this->record);
                    $this->refreshFormData(['file_path']);

                    Notification::make()
                        ->title('QR code régénéré')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\GeneratedQrCodes\Pages;

use App\Filament\Resources\GeneratedQrCodes\GeneratedQrCodeResource;
use App\Services\QrCode\QrCodeGeneratorService;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'un QR code et régénération du PNG.
 */
class EditGeneratedQrCode extends EditRecord
{
    protected static string $resource = GeneratedQrCodeResource::class;

    /**
     * @return void
     */
    protected function afterSave(): void
    {
        app(QrCodeGeneratorService::class)->generateAndStore($this->record);
    }
}

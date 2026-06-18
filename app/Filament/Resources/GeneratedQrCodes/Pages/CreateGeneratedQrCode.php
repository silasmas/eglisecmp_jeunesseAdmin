<?php

namespace App\Filament\Resources\GeneratedQrCodes\Pages;

use App\Filament\Resources\GeneratedQrCodes\GeneratedQrCodeResource;
use App\Services\QrCode\QrCodeGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

/**
 * Création d'un QR code et génération immédiate du PNG.
 */
class CreateGeneratedQrCode extends CreateRecord
{
    protected static string $resource = GeneratedQrCodeResource::class;

    /**
     * @param array<string, mixed> $data Données du formulaire
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    /**
     * @return void
     */
    protected function afterCreate(): void
    {
        app(QrCodeGeneratorService::class)->generateAndStore($this->record);
    }
}

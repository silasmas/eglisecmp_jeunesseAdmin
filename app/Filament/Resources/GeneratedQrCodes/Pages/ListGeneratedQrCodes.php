<?php

namespace App\Filament\Resources\GeneratedQrCodes\Pages;

use App\Filament\Resources\GeneratedQrCodes\GeneratedQrCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Page liste des QR codes.
 */
class ListGeneratedQrCodes extends ListRecords
{
    protected static string $resource = GeneratedQrCodeResource::class;

    /**
     * @return array<int, CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

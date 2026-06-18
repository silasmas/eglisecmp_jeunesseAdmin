<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations\Pages;

use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des dons volontaires retraite.
 */
class ListRetreatVoluntaryDonations extends ListRecords
{
    protected static string $resource = RetreatVoluntaryDonationResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use App\Support\RetreatLogisticsFormSupport;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'une chambre rattachée à une retraite opérationnelle.
 */
class CreateRetreatChambre extends CreateRecord
{
    protected static string $resource = RetreatChambreResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(RetreatLogisticsFormSupport::class)->prepareCreateData($data);
    }
}

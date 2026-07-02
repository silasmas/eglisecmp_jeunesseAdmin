<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Support\RetreatLogisticsFormSupport;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un atelier rattaché à une retraite opérationnelle.
 */
class CreateRetreatAtelier extends CreateRecord
{
    protected static string $resource = RetreatAtelierResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(RetreatLogisticsFormSupport::class)->prepareCreateData($data);
    }
}

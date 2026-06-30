<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use App\Models\ChurchEvent;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

/**
 * Création d'une chambre rattachée à l'événement retraite opérationnel.
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
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event === null) {
            throw ValidationException::withMessages([
                'nom' => 'Aucune retraite opérationnelle active : activez un événement non clôturé avant de créer une chambre.',
            ]);
        }

        $data['event_id'] = $event->getKey();
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}

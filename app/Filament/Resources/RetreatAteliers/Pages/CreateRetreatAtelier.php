<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Models\ChurchEvent;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

/**
 * Création d'un atelier rattaché à l'événement retraite opérationnel.
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
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event === null) {
            throw ValidationException::withMessages([
                'numero' => 'Aucune retraite opérationnelle active : activez un événement non clôturé avant de créer un atelier.',
            ]);
        }

        $data['event_id'] = $event->getKey();
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}

<?php

namespace App\Filament\Resources\RegistrationFormConfigs\Pages;

use App\Filament\Resources\RegistrationFormConfigs\RegistrationFormConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Page de liste des configurations du formulaire d'inscription.
 */
class ListRegistrationFormConfigs extends ListRecords
{
    protected static string $resource = RegistrationFormConfigResource::class;

    /**
     * Actions disponibles dans l'en-tête.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouvelle configuration'),
        ];
    }
}

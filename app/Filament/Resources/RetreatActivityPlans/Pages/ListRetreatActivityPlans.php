<?php

namespace App\Filament\Resources\RetreatActivityPlans\Pages;

use App\Filament\Resources\RetreatActivityPlans\RetreatActivityPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRetreatActivityPlans extends ListRecords
{
    protected static string $resource = RetreatActivityPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Créer une activité de la retraite')
                ->url(fn (): string => RetreatActivityPlanResource::getUrl('create')),
        ];
    }
}

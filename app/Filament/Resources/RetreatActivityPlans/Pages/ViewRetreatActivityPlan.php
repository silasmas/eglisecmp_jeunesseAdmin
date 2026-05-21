<?php

namespace App\Filament\Resources\RetreatActivityPlans\Pages;

use App\Filament\Resources\RetreatActivityPlans\RetreatActivityPlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatActivityPlan extends ViewRecord
{
    protected static string $resource = RetreatActivityPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

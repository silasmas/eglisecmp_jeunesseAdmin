<?php

namespace App\Filament\Resources\RetreatPolicies\Pages;

use App\Filament\Resources\RetreatPolicies\RetreatPolicyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatPolicy extends ViewRecord
{
    protected static string $resource = RetreatPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

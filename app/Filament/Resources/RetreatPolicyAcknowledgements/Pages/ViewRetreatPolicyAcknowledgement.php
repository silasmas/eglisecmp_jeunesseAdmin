<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Pages;

use App\Filament\Resources\RetreatPolicyAcknowledgements\RetreatPolicyAcknowledgementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatPolicyAcknowledgement extends ViewRecord
{
    protected static string $resource = RetreatPolicyAcknowledgementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

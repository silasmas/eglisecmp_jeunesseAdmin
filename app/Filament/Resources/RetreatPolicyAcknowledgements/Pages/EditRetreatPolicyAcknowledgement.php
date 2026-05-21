<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Pages;

use App\Filament\Resources\RetreatPolicyAcknowledgements\RetreatPolicyAcknowledgementResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatPolicyAcknowledgement extends EditRecord
{
    protected static string $resource = RetreatPolicyAcknowledgementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

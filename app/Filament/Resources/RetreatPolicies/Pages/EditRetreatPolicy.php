<?php

namespace App\Filament\Resources\RetreatPolicies\Pages;

use App\Filament\Resources\RetreatPolicies\RetreatPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatPolicy extends EditRecord
{
    protected static string $resource = RetreatPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

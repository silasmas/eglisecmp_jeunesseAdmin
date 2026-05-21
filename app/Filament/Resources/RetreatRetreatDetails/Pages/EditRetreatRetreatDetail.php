<?php

namespace App\Filament\Resources\RetreatRetreatDetails\Pages;

use App\Filament\Resources\RetreatRetreatDetails\RetreatRetreatDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatRetreatDetail extends EditRecord
{
    protected static string $resource = RetreatRetreatDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

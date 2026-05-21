<?php

namespace App\Filament\Resources\RetreatSessions\Pages;

use App\Filament\Resources\RetreatSessions\RetreatSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatSession extends EditRecord
{
    protected static string $resource = RetreatSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatAtelier extends EditRecord
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

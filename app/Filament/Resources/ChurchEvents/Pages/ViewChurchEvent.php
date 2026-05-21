<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChurchEvent extends ViewRecord
{
    protected static string $resource = ChurchEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

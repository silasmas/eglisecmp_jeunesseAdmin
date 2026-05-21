<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Pages;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatActivityAttendance extends ViewRecord
{
    protected static string $resource = RetreatActivityAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

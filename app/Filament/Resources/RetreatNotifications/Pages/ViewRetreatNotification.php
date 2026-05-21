<?php

namespace App\Filament\Resources\RetreatNotifications\Pages;

use App\Filament\Resources\RetreatNotifications\RetreatNotificationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatNotification extends ViewRecord
{
    protected static string $resource = RetreatNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

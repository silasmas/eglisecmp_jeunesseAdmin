<?php

namespace App\Filament\Resources\RetreatNotifications\Pages;

use App\Filament\Resources\RetreatNotifications\RetreatNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatNotification extends EditRecord
{
    protected static string $resource = RetreatNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

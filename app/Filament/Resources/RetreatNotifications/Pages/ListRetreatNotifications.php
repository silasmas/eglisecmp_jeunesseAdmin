<?php

namespace App\Filament\Resources\RetreatNotifications\Pages;

use App\Filament\Resources\RetreatNotifications\RetreatNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRetreatNotifications extends ListRecords
{
    protected static string $resource = RetreatNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

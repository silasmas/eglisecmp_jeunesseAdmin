<?php

namespace App\Filament\Resources\SmsOperators\Pages;

use App\Filament\Resources\SmsOperators\SmsOperatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsOperators extends ListRecords
{
    protected static string $resource = SmsOperatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

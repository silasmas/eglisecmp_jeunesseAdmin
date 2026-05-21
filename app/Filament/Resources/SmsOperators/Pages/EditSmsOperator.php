<?php

namespace App\Filament\Resources\SmsOperators\Pages;

use App\Filament\Resources\SmsOperators\SmsOperatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmsOperator extends EditRecord
{
    protected static string $resource = SmsOperatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

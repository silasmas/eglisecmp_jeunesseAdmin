<?php

namespace App\Filament\Resources\RetreatPayments\Pages;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRetreatPayment extends EditRecord
{
    protected static string $resource = RetreatPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

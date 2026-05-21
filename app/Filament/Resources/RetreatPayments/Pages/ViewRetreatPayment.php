<?php

namespace App\Filament\Resources\RetreatPayments\Pages;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatPayment extends ViewRecord
{
    protected static string $resource = RetreatPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

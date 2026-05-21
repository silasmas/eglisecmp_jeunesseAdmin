<?php

namespace App\Filament\Resources\RetreatPayments\Pages;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use App\Filament\Resources\RetreatPayments\Widgets\RetreatPaymentsStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRetreatPayments extends ListRecords
{
    protected static string $resource = RetreatPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatPaymentsStats::class,
        ];
    }
}

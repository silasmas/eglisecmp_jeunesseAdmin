<?php

namespace App\Filament\Resources\RetreatPayments\Pages;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use App\Filament\Support\RetreatPaymentFlexPayFilamentActions;
use App\Filament\Support\RetreatInscriptionResumeFilamentAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail d'un paiement d'inscription retraite.
 */
class ViewRetreatPayment extends ViewRecord
{
    protected static string $resource = RetreatPaymentResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            RetreatInscriptionResumeFilamentAction::make(),
            RetreatPaymentFlexPayFilamentActions::recheckAction(),
            RetreatPaymentFlexPayFilamentActions::relaunchAction(),
            EditAction::make(),
        ];
    }
}

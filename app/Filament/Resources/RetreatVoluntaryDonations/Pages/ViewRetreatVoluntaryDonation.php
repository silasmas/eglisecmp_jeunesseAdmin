<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations\Pages;

use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use App\Models\RetreatVoluntaryDonation;
use App\Services\RetreatDonation\RetreatVoluntaryDonationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail d'un don volontaire retraite.
 */
class ViewRetreatVoluntaryDonation extends ViewRecord
{
    protected static string $resource = RetreatVoluntaryDonationResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('recheckFlexPay')
                ->label('Relancer vérification FlexPay')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->canRecheckFlexPay($this->getRecord()))
                ->requiresConfirmation()
                ->modalHeading('Relancer la vérification du paiement')
                ->modalDescription('Interroge l’opérateur Mobile Money pour confirmer si le don a été encaissé.')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $service = app(RetreatVoluntaryDonationService::class);

                    try {
                        $result = $service->recheckFlexPayMobilePayment($record);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Vérification impossible')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($result['confirmed']) {
                        Notification::make()
                            ->title('Paiement confirmé')
                            ->body($result['message'])
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Pas encore confirmé')
                        ->body($result['message'])
                        ->warning()
                        ->send();
                }),
        ];
    }

    /**
     * @param RetreatVoluntaryDonation $record Don affiché
     * @return bool
     */
    protected function canRecheckFlexPay(RetreatVoluntaryDonation $record): bool
    {
        if ($record->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return false;
        }

        if ($record->donation_kind !== RetreatVoluntaryDonation::KIND_CASH) {
            return false;
        }

        if ($record->status === RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED) {
            return false;
        }

        return $record->payment_channel === 'mobile_money' || filled($record->provider_reference);
    }
}

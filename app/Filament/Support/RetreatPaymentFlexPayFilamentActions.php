<?php

namespace App\Filament\Support;

use App\Models\RetreatPayment;
use App\Services\RetreatPaymentFlexPayService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Actions Filament partagées : vérification et relance FlexPay sur un paiement retraite.
 */
class RetreatPaymentFlexPayFilamentActions
{
    /**
     * Action de vérification du statut FlexPay (polling admin).
     *
     * @return Action
     */
    public static function recheckAction(): Action
    {
        return Action::make('recheckFlexPay')
            ->label('Vérifier FlexPay')
            ->icon('heroicon-o-magnifying-glass')
            ->color('info')
            ->visible(fn (RetreatPayment $record): bool => self::service()->canRecheck($record))
            ->authorize(fn (RetreatPayment $record): bool => auth()->user()?->can('recheckFlexPay', $record) ?? false)
            ->requiresConfirmation()
            ->modalHeading('Vérifier le statut FlexPay')
            ->modalDescription('Interroge FlexPay pour savoir si le paiement a été encaissé.')
            ->action(function (RetreatPayment $record): void {
                self::handleRecheck($record);
            });
    }

    /**
     * Action de relance d'une demande FlexPay (mobile ou carte).
     *
     * @return Action
     */
    public static function relaunchAction(): Action
    {
        return Action::make('relaunchFlexPay')
            ->label('Relancer FlexPay')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (RetreatPayment $record): bool => self::service()->canRelaunch($record))
            ->authorize(fn (RetreatPayment $record): bool => auth()->user()?->can('relaunchFlexPay', $record) ?? false)
            ->requiresConfirmation()
            ->modalHeading('Relancer le paiement FlexPay')
            ->modalDescription('Envoie une nouvelle demande de paiement au participant (Mobile Money ou carte).')
            ->form(fn (RetreatPayment $record): array => self::relaunchFormSchema($record))
            ->action(function (RetreatPayment $record, array $data): void {
                self::handleRelaunch($record, isset($data['phone']) ? (string) $data['phone'] : null);
            });
    }

    /**
     * @param RetreatPayment $record Paiement affiché
     * @return array<int, TextInput>
     */
    protected static function relaunchFormSchema(RetreatPayment $record): array
    {
        if ($record->channel !== 'mobile_money') {
            return [];
        }

        return [
            TextInput::make('phone')
                ->label('Téléphone Mobile Money')
                ->placeholder('24389XXXXXXX')
                ->default($record->phone)
                ->required()
                ->helperText('12 chiffres commençant par 243, sans + ni 0 initial.'),
        ];
    }

    /**
     * Exécute la vérification FlexPay et affiche une notification Filament.
     *
     * @param RetreatPayment $record Paiement concerné
     * @return void
     */
    public static function handleRecheck(RetreatPayment $record): void
    {
        $service = self::service();

        try {
            $result = $service->recheckPayment($record);
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
    }

    /**
     * Exécute la relance FlexPay et affiche une notification Filament.
     *
     * @param RetreatPayment $record Paiement concerné
     * @param string|null $phone Numéro mobile (canal mobile_money)
     * @return void
     */
    public static function handleRelaunch(RetreatPayment $record, ?string $phone): void
    {
        $service = self::service();

        try {
            $result = $service->relaunchPayment($record, $phone);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Relance impossible')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $notification = Notification::make()
            ->title('FlexPay relancé')
            ->body($result['message'])
            ->success();

        if (filled($result['redirect_url'] ?? null)) {
            $notification->actions([
                Action::make('openFlexPay')
                    ->label('Ouvrir la page carte')
                    ->url((string) $result['redirect_url'])
                    ->openUrlInNewTab(),
            ]);
        }

        $notification->send();
    }

    /**
     * @return RetreatPaymentFlexPayService
     */
    protected static function service(): RetreatPaymentFlexPayService
    {
        return app(RetreatPaymentFlexPayService::class);
    }
}

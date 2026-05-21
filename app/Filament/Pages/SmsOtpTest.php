<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SmsMessageLogs\SmsMessageLogResource;
use App\Services\KeccelSmsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;
use UnitEnum;

class SmsOtpTest extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Test OTP SMS';

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.sms-otp-test';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('history')
                ->label('Voir l’historique SMS')
                ->icon('heroicon-o-inbox-stack')
                ->url(SmsMessageLogResource::getUrl('index')),
            Action::make('sendTestOtp')
                ->label('Envoyer un OTP test')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading('Tester l’envoi OTP par SMS')
                ->form([
                    TextInput::make('phone')
                        ->label('Téléphone destinataire')
                        ->placeholder('2438XXXXXXXX')
                        ->required(),
                    TextInput::make('otp')
                        ->label('Code OTP')
                        ->default(fn (): string => (string) random_int(100000, 999999))
                        ->required()
                        ->length(6)
                        ->numeric(),
                ])
                ->action(function (array $data): void {
                    try {
                        app(KeccelSmsService::class)->send(
                            (string) $data['phone'],
                            'Code OTP test CMP: '.$data['otp'].'. Valable 10 minutes.',
                            'dashboard_otp_test'
                        );
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Échec d’envoi SMS OTP')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('SMS OTP envoyé')
                        ->body('Le test SMS a été transmis à Keccel.')
                        ->success()
                        ->send();
                }),
        ];
    }
}

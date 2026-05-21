<?php

namespace App\Filament\Resources\SmsMessageLogs\Tables;

use App\Models\SmsMessageLog;
use App\Services\KeccelSmsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsMessageLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('context')
                    ->label('Contexte')
                    ->badge()
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('operator.name')
                    ->label('Opérateur')
                    ->placeholder('Env/.config')
                    ->searchable(),
                TextColumn::make('recipient')
                    ->label('Destinataire')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('http_method')
                    ->label('Méthode')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('http_status')
                    ->label('HTTP')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('delivery_status')
                    ->label('Livraison')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'DELIVERED' => 'success',
                        'FAILED', 'ERROR' => 'danger',
                        'PENDING' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('provider_reference')
                    ->label('Référence')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('provider_response')
                    ->label('Retour Keccel')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'sent' => 'Envoyé',
                        'delivered' => 'Livré',
                        'failed' => 'Échec',
                    ]),
                SelectFilter::make('context')
                    ->label('Contexte')
                    ->options([
                        'dashboard_otp_test' => 'Test OTP dashboard',
                        'parent_contact_otp' => 'OTP parent/tuteur',
                        'retreat_payment_confirmation' => 'Confirmation paiement',
                    ]),
            ])
            ->recordActions([
                Action::make('checkDelivery')
                    ->label('Vérifier livraison')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (SmsMessageLog $record): bool => filled($record->provider_reference))
                    ->action(function (SmsMessageLog $record): void {
                        try {
                            $updated = app(KeccelSmsService::class)->refreshDelivery($record);
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Livraison non vérifiée')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Statut de livraison actualisé')
                            ->body('Statut : '.($updated->delivery_status ?: 'inconnu'))
                            ->success()
                            ->send();
                    }),
                Action::make('details')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Détails de l’envoi SMS')
                    ->modalContent(fn ($record) => view('filament.resources.sms-message-logs.details', [
                        'record' => $record,
                    ])),
            ]);
    }
}

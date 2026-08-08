<?php

namespace App\Filament\Resources\SmsMessageLogs\Tables;

use App\Jobs\RefreshSmsDeliveriesJob;
use App\Models\SmsMessageLog;
use App\Services\KeccelSmsService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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
                    ->label('Accusé (DLR)')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'DELIVERED' => 'Arrivé',
                        'READ' => 'Lu',
                        'FAILED' => 'Échec livraison',
                        'ERROR' => 'Erreur requête DLR',
                        'NOT_FOUND' => 'DLR indisponible',
                        'PENDING' => 'En attente',
                        'UNKNOWN', '' => '—',
                        default => (string) $state,
                    })
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'DELIVERED', 'READ' => 'success',
                        'FAILED', 'REJECTED', 'EXPIRED' => 'danger',
                        'ERROR' => 'danger',
                        'NOT_FOUND' => 'gray',
                        'PENDING', 'BUFFERED', 'ENROUTE', 'ACCEPTED' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('delivery_checked_at')
                    ->label('DLR vérifié')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
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
                        'delivered' => 'Livré / arrivé',
                        'failed' => 'Échec',
                    ]),
                SelectFilter::make('delivery_status')
                    ->label('Accusé')
                    ->options([
                        'PENDING' => 'En attente',
                        'DELIVERED' => 'Arrivé',
                        'READ' => 'Lu',
                        'FAILED' => 'Échec',
                        'ERROR' => 'Erreur',
                    ]),
                SelectFilter::make('context')
                    ->label('Contexte')
                    ->options([
                        'dashboard_otp_test' => 'Test OTP dashboard',
                        'parent_contact_otp' => 'OTP parent/tuteur',
                        'retreat_payment_confirmation' => 'Confirmation paiement',
                        'sms_campaign' => 'Campagne SMS',
                        'operator_connection_test' => 'Test opérateur',
                    ]),
            ])
            ->recordActions([
                Action::make('checkDelivery')
                    ->label('Vérifier accusé')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (SmsMessageLog $record): bool => filled($record->provider_reference))
                    ->action(function (SmsMessageLog $record): void {
                        try {
                            $updated = app(KeccelSmsService::class)->refreshDelivery($record);
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Accusé non vérifié')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $dlr = strtoupper((string) ($updated->delivery_status ?: ''));
                        $label = match ($dlr) {
                            'DELIVERED' => 'Arrivé sur le téléphone',
                            'FAILED' => 'Échec de livraison',
                            'NOT_FOUND' => 'DLR indisponible chez Keccel (SMS peut quand même être reçu)',
                            'PENDING' => 'Encore en attente côté opérateur',
                            'ERROR' => 'Erreur de requête DLR',
                            default => ($updated->delivery_status ?: 'inconnu'),
                        };

                        $notification = Notification::make()
                            ->title('Accusé de réception actualisé')
                            ->body('Livraison : '.$label.' — statut envoi : '.$updated->status);

                        if (in_array($dlr, ['DELIVERED', 'READ'], true)) {
                            $notification->success();
                        } elseif (in_array($dlr, ['FAILED', 'ERROR'], true)) {
                            $notification->danger();
                        } else {
                            $notification->warning();
                        }

                        $notification->send();
                    }),
                Action::make('details')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Détails de l’envoi SMS')
                    ->modalContent(fn ($record) => view('filament.resources.sms-message-logs.details', [
                        'record' => $record,
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('refreshDeliveries')
                        ->label('Vérifier accusés')
                        ->icon('heroicon-o-signal')
                        ->action(function (Collection $records): void {
                            $ids = $records
                                ->filter(fn (SmsMessageLog $log): bool => filled($log->provider_reference))
                                ->pluck('id')
                                ->map(fn ($id): int => (int) $id)
                                ->values()
                                ->all();

                            if ($ids === []) {
                                Notification::make()
                                    ->title('Aucune référence Keccel')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            RefreshSmsDeliveriesJob::dispatch($ids, Auth::id() ? (int) Auth::id() : null);

                            Notification::make()
                                ->title('Vérification mise en file')
                                ->body(count($ids).' SMS sélectionné(s).')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}

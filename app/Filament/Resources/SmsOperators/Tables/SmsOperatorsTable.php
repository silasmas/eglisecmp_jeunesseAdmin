<?php

namespace App\Filament\Resources\SmsOperators\Tables;

use App\Models\SmsOperator;
use App\Services\KeccelSmsService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class SmsOperatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('provider')
                    ->label('Fournisseur')
                    ->badge()
                    ->searchable(),
                TextColumn::make('sender')
                    ->label('Expéditeur')
                    ->searchable(),
                TextColumn::make('send_method')
                    ->label('Méthode')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('remaining_sms')
                    ->label('SMS restants')
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn ($state): string => is_numeric($state) && (int) $state < 0 ? 'danger' : 'gray')
                    ->description(function (SmsOperator $record): ?string {
                        $meta = app(KeccelSmsService::class)->parseBalanceMeta($record->last_balance_response);
                        if ($meta['is_expired']) {
                            return 'Compte/crédits expirés'.($meta['expiration'] ? ' ('.$meta['expiration'].')' : '');
                        }
                        if (is_numeric($record->remaining_sms) && (int) $record->remaining_sms < 0) {
                            return 'Solde négatif — recharger le compte Keccel';
                        }

                        return $meta['expiration'] ? 'Expire : '.$meta['expiration'] : null;
                    }),
                TextColumn::make('last_balance_checked_at')
                    ->label('Solde vérifié')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_balance_response')
                    ->label('Réponse solde')
                    ->limit(45)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('testConnection')
                    ->label('Tester connexion')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalHeading('Tester cet opérateur SMS')
                    ->form([
                        TextInput::make('phone')
                            ->label('Téléphone destinataire')
                            ->placeholder('2438XXXXXXXX')
                            ->required(),
                        TextInput::make('message')
                            ->label('Message test')
                            ->default('Test connexion SMS CMP')
                            ->required(),
                    ])
                    ->action(function (SmsOperator $record, array $data): void {
                        try {
                            $service = app(KeccelSmsService::class);
                            $service->send(
                                (string) $data['phone'],
                                (string) $data['message'],
                                'operator_connection_test',
                                $record
                            );
                            $log = $service->lastLog();
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Test connexion échoué')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Test connexion envoyé')
                            ->body(
                                'HTTP : '.($log?->http_status ?: '—')
                                ."\nRéponse : ".($log?->provider_response ?: '—')
                            )
                            ->success()
                            ->send();
                    }),
                Action::make('refreshBalance')
                    ->label('Actualiser solde')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (SmsOperator $record): void {
                        $service = app(KeccelSmsService::class);
                        try {
                            $balance = $service->refreshBalance($record);
                        } catch (Throwable $e) {
                            report($e);

                            $record->refresh();
                            $description = $service->describeResponse($record->last_balance_response);

                            Notification::make()
                                ->title('Solde SMS non récupéré')
                                ->body($e->getMessage()."\nType réponse : ".$description['type']."\nRéponse : ".($description['preview'] ?: '—'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->refresh();
                        $description = $service->describeResponse($record->last_balance_response);
                        $meta = $service->parseBalanceMeta($record->last_balance_response);
                        $lines = [
                            $balance === null ? 'Réponse reçue, solde non numérique.' : "SMS restants : {$balance}",
                            'Type réponse : '.$description['type'],
                            'Réponse : '.($description['preview'] ?: '—'),
                        ];
                        if ($meta['expiration']) {
                            $lines[] = 'Expiration : '.$meta['expiration'];
                        }
                        if ($meta['account_status']) {
                            $lines[] = 'Statut compte : '.$meta['account_status'];
                        }

                        $isWarning = ($balance !== null && $balance < 0) || $meta['is_expired'];
                        $notification = Notification::make()
                            ->title($isWarning ? 'Solde SMS actualisé (attention)' : 'Solde SMS actualisé')
                            ->body(implode("\n", $lines));

                        if ($isWarning) {
                            $notification->warning();
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

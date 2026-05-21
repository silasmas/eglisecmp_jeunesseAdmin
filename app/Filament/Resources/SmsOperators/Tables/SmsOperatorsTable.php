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
                    ->sortable(),
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

                        Notification::make()
                            ->title('Solde SMS actualisé')
                            ->body(
                                ($balance === null ? 'Réponse reçue, solde non numérique.' : "SMS restants : {$balance}")
                                ."\nType réponse : ".$description['type']
                                ."\nRéponse : ".($description['preview'] ?: '—')
                            )
                            ->success()
                            ->send();
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

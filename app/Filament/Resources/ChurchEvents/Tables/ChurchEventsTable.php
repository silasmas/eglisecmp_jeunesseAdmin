<?php

namespace App\Filament\Resources\ChurchEvents\Tables;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Models\ChurchEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Slimani\MediaManager\Tables\Columns\MediaColumn;
use UnitEnum;
use Wezlo\FilamentRecordWatcher\Actions\UnwatchAction;
use Wezlo\FilamentRecordWatcher\Actions\WatchAction;

class ChurchEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                MediaColumn::make('afficheMedia')
                    ->label('Affiche')
                    ->conversion('')
                    ->square()
                    ->sticky(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sticky(),
                TextColumn::make('statut_temporel')
                    ->label('Statut')
                    ->badge()
                    ->state(function (ChurchEvent $record): string {
                        if ($record->start_at?->isFuture()) {
                            return 'Futur';
                        }

                        if ($record->end_at?->isPast()) {
                            return 'Passe';
                        }

                        return 'En cours';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Passe' => 'danger',
                        'En cours' => 'warning',
                        'Futur' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->label("Type d'evenement")
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Debut')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lieu')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('Capacite')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_to_pay')
                    ->label('Montant a payer')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Devise')
                    ->searchable(),
                SelectColumn::make('access_auth_mode')
                    ->label("Mode d'authentification")
                    ->options([
                        EventAccessAuthMode::Password->value => EventAccessAuthMode::Password->label(),
                        EventAccessAuthMode::Otp->value => EventAccessAuthMode::Otp->label(),
                    ])
                    ->getStateUsing(fn (ChurchEvent $record): string => self::normalizeEnumValue(
                        $record->access_auth_mode,
                        EventAccessAuthMode::Password->value,
                    ))
                    ->updateStateUsing(function (ChurchEvent $record, mixed $state): string {
                        $value = self::normalizeEnumValue($state, EventAccessAuthMode::Password->value);

                        $record->forceFill(['access_auth_mode' => $value])->save();

                        return $value;
                    })
                    ->selectablePlaceholder(false),
                SelectColumn::make('access_otp_channel')
                    ->label('Canal OTP')
                    ->options([
                        EventAccessOtpChannel::Sms->value => EventAccessOtpChannel::Sms->label(),
                        EventAccessOtpChannel::Email->value => EventAccessOtpChannel::Email->label(),
                    ])
                    ->getStateUsing(fn (ChurchEvent $record): string => self::normalizeEnumValue(
                        $record->access_otp_channel,
                        EventAccessOtpChannel::Email->value,
                    ))
                    ->updateStateUsing(function (ChurchEvent $record, mixed $state): string {
                        $value = self::normalizeEnumValue($state, EventAccessOtpChannel::Email->value);

                        $record->forceFill(['access_otp_channel' => $value])->save();

                        return $value;
                    })
                    ->selectablePlaceholder(false),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                IconColumn::make('is_publicly_closed')
                    ->label('Public fermé')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis a jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                WatchAction::make(),
                UnwatchAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function normalizeEnumValue(mixed $value, ?string $default = null): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        return blank($value) ? $default : (string) $value;
    }
}

<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use App\Models\ChurchEvent;
use App\Services\ChurchEventArchiveService;
use App\Services\RetreatLogisticsReplicationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use UnitEnum;

/**
 * Édition d'un événement (affiche, archivage, reconduction logistique).
 */
class EditChurchEvent extends EditRecord
{
    protected static string $resource = ChurchEventResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('replicateLogistics')
                ->label('Reconduire ateliers et chambres')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn (ChurchEvent $record): bool => ! $record->isArchived())
                ->form([
                    Select::make('source_event_id')
                        ->label('Copier depuis la retraite')
                        ->options(fn (ChurchEvent $record): array => app(RetreatLogisticsReplicationService::class)
                            ->sourceEventOptions($record->getKey()))
                        ->searchable()
                        ->required()
                        ->helperText('Duplique les ateliers et chambres utilisés lors de la retraite choisie.'),
                ])
                ->action(function (ChurchEvent $record, array $data): void {
                    $source = ChurchEvent::query()->findOrFail($data['source_event_id']);
                    $result = app(RetreatLogisticsReplicationService::class)->replicateFromEvent($source, $record);

                    Notification::make()
                        ->title('Logistique reconduite')
                        ->body("{$result['ateliers']} atelier(s) et {$result['chambres']} chambre(s) créés pour cette retraite.")
                        ->success()
                        ->send();
                }),
            Action::make('archive')
                ->label('Archiver la retraite')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Archiver cette retraite')
                ->modalDescription('La retraite sera clôturée et ses participants ne compteront plus dans les vues opérationnelles. Consultez l\'historique pour les retrouver.')
                ->visible(fn (ChurchEvent $record): bool => ! $record->isArchived())
                ->action(function (ChurchEvent $record): void {
                    app(ChurchEventArchiveService::class)->archive($record);

                    Notification::make()
                        ->title('Retraite archivée')
                        ->body('Les participants restent consultables dans Historique retraites.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['is_active', 'is_publicly_closed', 'archived_at']);
                }),
            DeleteAction::make()
                ->visible(fn (ChurchEvent $record): bool => ! $record->isArchived()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['access_auth_mode'] = $this->normalizeEnumValue(
            $data['access_auth_mode'] ?? null,
            EventAccessAuthMode::Password->value
        );
        $data['access_otp_channel'] = $this->normalizeEnumValue($data['access_otp_channel'] ?? null);

        if (filled($data['affiche'] ?? null)) {
            $data['affiche_id'] = null;
        }

        return $data;
    }

    /**
     * @param  mixed  $value Valeur enum ou scalaire
     * @param  string|null  $default Valeur par défaut
     * @return string|null
     */
    protected function normalizeEnumValue(mixed $value, ?string $default = null): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        return blank($value) ? $default : (string) $value;
    }
}

<?php

namespace App\Filament\Resources\ChurchEventHistories\Support;

use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use App\Models\ChurchEvent;
use App\Services\ChurchEventArchiveService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * Action Filament « Restaurer » pour une retraite archivée.
 */
final class RestoreArchivedChurchEventAction
{
    private function __construct()
    {
    }

    /**
     * @return Action
     */
    public static function make(): Action
    {
        return Action::make('restore')
            ->label('Restaurer')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Restaurer cette retraite')
            ->modalDescription('La retraite réapparaît dans l\'administration avec ses participants, ateliers et chambres. L\'accès public est rouvert.')
            ->form([
                Toggle::make('activate')
                    ->label('Activer comme événement courant')
                    ->helperText('Un seul événement actif à la fois. L\'autre événement actif sera désactivé.')
                    ->default(false),
            ])
            ->action(function (ChurchEvent $record, array $data, $livewire): void {
                $restored = app(ChurchEventArchiveService::class)->restore(
                    $record,
                    (bool) ($data['activate'] ?? false)
                );

                Notification::make()
                    ->title('Retraite restaurée')
                    ->body(sprintf(
                        '« %s » est de nouveau opérationnelle (%d participant(s), %d atelier(s), %d chambre(s)).',
                        $restored->name,
                        $restored->participants()->count(),
                        $restored->ateliers()->count(),
                        $restored->chambres()->count(),
                    ))
                    ->success()
                    ->send();

                $livewire->redirect(ChurchEventResource::getUrl('edit', ['record' => $restored]));
            });
    }
}

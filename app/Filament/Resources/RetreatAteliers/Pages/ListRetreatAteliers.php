<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Filament\Resources\RetreatAteliers\Widgets\RetreatAteliersStats;
use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierQuarantineNotifier;
use App\Services\RetreatPlacementAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatAteliers extends ListRecords
{
    protected static string $resource = RetreatAtelierResource::class;

    protected function getHeaderActions(): array
    {
        $quarantineCount = RetreatParticipant::query()->where('atelier_quarantine', true)->count();

        return [
            Action::make('reassignQuarantine')
                ->label('Réaffecter la quarantaine atelier')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->visible($quarantineCount > 0)
                ->requiresConfirmation()
                ->modalHeading('Réaffecter tous les participants en quarantaine')
                ->modalDescription(fn (): string => sprintf(
                    '%d participant(s) attendent une affectation atelier. Une nouvelle tentative automatique sera lancée pour chacun.',
                    $quarantineCount,
                ))
                ->action(function (): void {
                    $placement = app(RetreatPlacementAssignmentService::class);
                    $stats = $placement->reassignAllQuarantinedParticipants();

                    app(RetreatAtelierQuarantineNotifier::class)->notifySuperAdminsReassignmentSummary(
                        $stats,
                        'Quarantaine atelier (tous)',
                    );

                    Notification::make()
                        ->title('Réaffectation quarantaine terminée')
                        ->body(sprintf(
                            '%d réaffecté(s), %d toujours en quarantaine.',
                            $stats['reassigned'],
                            $stats['quarantined'],
                        ))
                        ->success()
                        ->send();
                }),
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatAteliersStats::class,
        ];
    }
}

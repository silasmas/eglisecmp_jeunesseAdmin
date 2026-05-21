<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Pages;

use App\Filament\Resources\RetreatParticipantMovements\RetreatParticipantMovementResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatParticipantMovements extends ListRecords
{
    protected static string $resource = RetreatParticipantMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mouvements_atelier')
                ->label('Mouvements par atelier')
                ->icon('heroicon-o-user-group')
                ->url(RetreatParticipantMovementResource::getUrl('atelier-mouvements')),
            CreateAction::make()->modal()->modalWidth(Width::SevenExtraLarge)->modalAlignment(Alignment::Center),
        ];
    }
}

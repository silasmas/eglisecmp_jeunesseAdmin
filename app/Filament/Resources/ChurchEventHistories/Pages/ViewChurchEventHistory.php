<?php

namespace App\Filament\Resources\ChurchEventHistories\Pages;

use App\Filament\Resources\ChurchEventHistories\ChurchEventHistoryResource;
use App\Filament\Resources\ChurchEventHistories\Widgets\ArchivedEventParticipantsWidget;
use App\Filament\Resources\ChurchEvents\Schemas\ChurchEventInfolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

/**
 * Détail d'une retraite archivée avec ses participants.
 */
class ViewChurchEventHistory extends ViewRecord
{
    protected static string $resource = ChurchEventHistoryResource::class;

    /**
     * @param  Schema  $schema Schéma Filament
     * @return Schema
     */
    public function infolist(Schema $schema): Schema
    {
        return ChurchEventInfolist::configure($schema);
    }

    /**
     * @return array<int, \Filament\Widgets\WidgetConfiguration>
     */
    protected function getFooterWidgets(): array
    {
        return [
            ArchivedEventParticipantsWidget::make([
                'eventId' => $this->getRecord()->getKey(),
            ]),
        ];
    }
}

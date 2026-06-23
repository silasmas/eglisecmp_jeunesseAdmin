<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Pages;

use App\Filament\Resources\RetreatParticipantDeletionLogs\RetreatParticipantDeletionLogResource;
use Filament\Resources\Pages\ListRecords;

class ListRetreatParticipantDeletionLogs extends ListRecords
{
    protected static string $resource = RetreatParticipantDeletionLogResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}

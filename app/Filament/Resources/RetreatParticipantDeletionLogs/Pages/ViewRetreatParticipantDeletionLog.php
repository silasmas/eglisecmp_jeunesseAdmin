<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs\Pages;

use App\Filament\Resources\RetreatParticipantDeletionLogs\RetreatParticipantDeletionLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRetreatParticipantDeletionLog extends ViewRecord
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

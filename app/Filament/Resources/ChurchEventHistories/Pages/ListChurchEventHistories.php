<?php

namespace App\Filament\Resources\ChurchEventHistories\Pages;

use App\Filament\Resources\ChurchEventHistories\ChurchEventHistoryResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des retraites archivées.
 */
class ListChurchEventHistories extends ListRecords
{
    protected static string $resource = ChurchEventHistoryResource::class;
}

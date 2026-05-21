<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use App\Filament\Resources\ChurchEvents\Widgets\ChurchEventsStats;
use App\Models\ChurchEvent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChurchEvents extends ListRecords
{
    protected static string $resource = ChurchEventResource::class;

    public function mount(): void
    {
        parent::mount();

        ChurchEvent::query()
            ->where('is_active', true)
            ->where('start_at', '<', now())
            ->update(['is_active' => false]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ChurchEventsStats::class,
        ];
    }
}

<?php

namespace App\Filament\Resources\RetreatChambres\Pages;

use App\Filament\Resources\RetreatChambres\RetreatChambreResource;
use App\Filament\Resources\RetreatChambres\Widgets\RetreatChambresStats;
use App\Support\RetreatLogisticsFormSupport;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatChambres extends ListRecords
{
    protected static string $resource = RetreatChambreResource::class;

    /**
     * @return string|null
     */
    public function getSubheading(): ?string
    {
        return app(RetreatLogisticsFormSupport::class)->listContextMessage();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modal()
                ->modalWidth(Width::SevenExtraLarge)
                ->modalAlignment(Alignment::Center)
                ->mutateFormDataUsing(fn (array $data): array => app(RetreatLogisticsFormSupport::class)->prepareCreateData($data))
                ->successRedirectUrl(fn (): string => RetreatChambreResource::getUrl('index')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatChambresStats::class,
        ];
    }
}

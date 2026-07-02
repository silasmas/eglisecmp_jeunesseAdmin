<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Pages\ManageRetreatAtelierQuarantine;
use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Filament\Resources\RetreatAteliers\Widgets\RetreatAteliersStats;
use App\Models\RetreatParticipant;
use App\Support\RetreatActiveEventScope;
use App\Support\RetreatLogisticsFormSupport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class ListRetreatAteliers extends ListRecords
{
    protected static string $resource = RetreatAtelierResource::class;

    /**
     * @return string|null
     */
    public function getSubheading(): ?string
    {
        return app(RetreatLogisticsFormSupport::class)->listContextMessage();
    }

    protected function getHeaderActions(): array
    {
        $quarantineCount = RetreatActiveEventScope::applyToParticipants(
            RetreatParticipant::query()->where('atelier_quarantine', true)
        )->count();

        return [
            Action::make('openAtelierQuarantine')
                ->label('Quarantaine atelier')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->url(fn (): string => ManageRetreatAtelierQuarantine::getUrl())
                ->badge($quarantineCount > 0 ? (string) $quarantineCount : null),
            CreateAction::make()
                ->modal()
                ->modalWidth(Width::SevenExtraLarge)
                ->modalAlignment(Alignment::Center)
                ->mutateFormDataUsing(fn (array $data): array => app(RetreatLogisticsFormSupport::class)->prepareCreateData($data))
                ->successRedirectUrl(fn (): string => RetreatAtelierResource::getUrl('index')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatAteliersStats::class,
        ];
    }
}

<?php

namespace App\Filament\Resources\ChurchEventHistories\Widgets;

use App\Models\ChurchEvent;
use App\Models\RetreatChambre;
use App\Services\RetreatEventLogisticsLifecycleService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Chambres utilisées lors d'une retraite archivée.
 */
class ArchivedEventChambresWidget extends TableWidget
{
    public ?int $eventId = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return string|null
     */
    public function getTableHeading(): ?string
    {
        return 'Chambres de cette retraite';
    }

    /**
     * @param  Table  $table Table Filament
     * @return Table
     */
    public function table(Table $table): Table
    {
        $chambreIds = $this->resolveChambreIds();

        return $table
            ->query(
                RetreatChambre::query()
                    ->when(
                        $chambreIds !== [],
                        fn (Builder $query): Builder => $query->whereIn('id', $chambreIds),
                        fn (Builder $query): Builder => $query->whereRaw('1 = 0')
                    )
                    ->with(['responsable'])
            )
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom'),
                TextColumn::make('sexe')
                    ->label('Sexe'),
                TextColumn::make('capacite')
                    ->label('Capacité'),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucune chambre affectée');
    }

    /**
     * @return array<int, int>
     */
    private function resolveChambreIds(): array
    {
        if (! $this->eventId) {
            return [];
        }

        $event = ChurchEvent::query()->find($this->eventId);

        if (! $event) {
            return [];
        }

        return app(RetreatEventLogisticsLifecycleService::class)->chambreIdsForEvent($event);
    }
}

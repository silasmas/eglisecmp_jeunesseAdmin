<?php

namespace App\Filament\Resources\ChurchEventHistories\Widgets;

use App\Models\RetreatChambre;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Chambres rattachées à une retraite archivée.
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
        return $table
            ->query(
                RetreatChambre::query()
                    ->when(
                        $this->eventId,
                        fn (Builder $query): Builder => $query->where('event_id', $this->eventId),
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
            ->emptyStateHeading('Aucune chambre pour cette édition');
    }
}

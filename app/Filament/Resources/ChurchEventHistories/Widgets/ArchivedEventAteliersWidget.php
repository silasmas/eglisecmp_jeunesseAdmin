<?php

namespace App\Filament\Resources\ChurchEventHistories\Widgets;

use App\Models\RetreatAtelier;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ateliers rattachés à une retraite archivée.
 */
class ArchivedEventAteliersWidget extends TableWidget
{
    public ?int $eventId = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return string|null
     */
    public function getTableHeading(): ?string
    {
        return 'Ateliers de cette retraite';
    }

    /**
     * @param  Table  $table Table Filament
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatAtelier::query()
                    ->when(
                        $this->eventId,
                        fn (Builder $query): Builder => $query->where('event_id', $this->eventId),
                        fn (Builder $query): Builder => $query->whereRaw('1 = 0')
                    )
                    ->with(['responsable', 'adjoint'])
            )
            ->columns([
                TextColumn::make('numero')
                    ->label('N°')
                    ->sortable(),
                TextColumn::make('age_min')
                    ->label('Âge min'),
                TextColumn::make('age_max')
                    ->label('Âge max'),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->placeholder('—'),
                TextColumn::make('adjoint.name')
                    ->label('Adjoint')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun atelier pour cette édition');
    }
}

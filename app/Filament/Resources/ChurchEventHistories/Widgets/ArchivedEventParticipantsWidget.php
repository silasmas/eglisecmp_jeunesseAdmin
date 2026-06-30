<?php

namespace App\Filament\Resources\ChurchEventHistories\Widgets;

use App\Models\RetreatParticipant;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des participants d'une retraite archivée (chambre, atelier, statut).
 */
class ArchivedEventParticipantsWidget extends TableWidget
{
    public ?int $eventId = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return string|null
     */
    public function getTableHeading(): ?string
    {
        return 'Participants de cette retraite';
    }

    /**
     * @param  Table  $table Table Filament
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatParticipant::query()
                    ->when(
                        $this->eventId,
                        fn (Builder $query): Builder => $query->where('event_id', $this->eventId)
                    )
                    ->with(['chambre', 'atelier'])
            )
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable(),
                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->toggleable(),
                TextColumn::make('chambre.nom')
                    ->label('Chambre')
                    ->placeholder('—'),
                TextColumn::make('atelier.numero')
                    ->label('Atelier n°')
                    ->placeholder('—'),
                IconColumn::make('paiement_valide')
                    ->label('Payé')
                    ->boolean(),
                IconColumn::make('present')
                    ->label('Présent')
                    ->boolean(),
                TextColumn::make('registration_status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->paginated([10, 25, 50]);
    }
}

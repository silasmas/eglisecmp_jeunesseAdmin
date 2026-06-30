<?php

namespace App\Filament\Pages;

use App\Filament\Support\QuarantinedAtelierAssignmentAction;
use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatAtelierProposalService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * File d'attente des participants en quarantaine atelier — propositions et validation admin.
 */
class ManageRetreatAtelierQuarantine extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Quarantaine ateliers';

    protected static ?string $title = 'Quarantaine atelier';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'quarantaine-ateliers';

    /**
     * @param array<string, mixed> $parameters Paramètres de route
     * @return bool
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->hasRole('super_admin') || $user->can('ViewAny:RetreatParticipant'));
    }

    /**
     * @return string|null Badge avec le nombre en quarantaine
     */
    public static function getNavigationBadge(): ?string
    {
        $count = RetreatParticipantResource::getEloquentQuery()->where('atelier_quarantine', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return string|null
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatParticipantResource::getEloquentQuery()
                    ->where('atelier_quarantine', true)
                    ->with(['event'])
                    ->orderByDesc('atelier_quarantine_at')
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Participant')
                    ->state(fn (RetreatParticipant $record): string => $record->full_name)
                    ->searchable(['nom', 'prenom']),
                TextColumn::make('age')
                    ->label('Âge')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sexe')
                    ->label('Sexe')
                    ->badge(),
                TextColumn::make('event.name')
                    ->label('Retraite')
                    ->placeholder('—'),
                TextColumn::make('proposal_summary')
                    ->label('Propositions du système')
                    ->state(fn (RetreatParticipant $record): string => app(RetreatAtelierProposalService::class)->summaryForParticipant($record))
                    ->wrap()
                    ->color('warning'),
                TextColumn::make('atelier_quarantine_at')
                    ->label('En quarantaine depuis')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                QuarantinedAtelierAssignmentAction::make('validateAssignment'),
            ])
            ->emptyStateHeading('Aucun participant en quarantaine')
            ->emptyStateDescription('Les inscriptions sans atelier compatible apparaîtront ici avec des propositions d\'affectation.');
    }
}

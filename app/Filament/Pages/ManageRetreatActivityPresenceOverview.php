<?php

namespace App\Filament\Pages;

use App\Models\RetreatAtelier;
use App\Models\User;
use App\Services\RetreatActivityPresenceOverviewService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Synthèse admin des présences par activité et par atelier (après pointage).
 */
class ManageRetreatActivityPresenceOverview extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Synthèse présences';

    protected static ?string $title = 'Synthèse des présences par activité';

    protected static string|UnitEnum|null $navigationGroup = 'Operations terrain';

    protected static ?int $navigationSort = 39;

    protected static ?string $slug = 'synthese-presences';

    public ?int $activityPlanId = null;

    /** @var array<string, mixed> */
    public array $overviewTotals = [];

    /** @var array<string, mixed>|null Cache synthèse activité courante */
    protected ?array $overviewCache = null;

    /**
     * @param array<string, mixed> $parameters Paramètres de route
     * @return bool
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->can('ViewAny:RetreatActivityAttendance') || $user->hasRole('super_admin')
        );
    }

    public function mount(): void
    {
        $options = app(RetreatActivityPresenceOverviewService::class)->activityOptions();

        if ($this->activityPlanId === null && $options !== []) {
            $this->activityPlanId = (int) array_key_first($options);
        }

        $this->refreshOverviewTotals();
    }

    /**
     * @return void
     */
    public function updatedActivityPlanId(): void
    {
        $this->overviewCache = null;
        $this->refreshOverviewTotals();
        $this->resetTable();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Activité')
                ->description('Choisissez une activité pour consulter les présences pointées par atelier.')
                ->schema([
                    Select::make('activityPlanId')
                        ->label('Activité')
                        ->options(fn (): array => app(RetreatActivityPresenceOverviewService::class)->activityOptions())
                        ->searchable()
                        ->live()
                        ->required(),
                ]),
            Section::make('Synthèse globale')
                ->description('Totaux consolidés pour l\'activité sélectionnée, tous ateliers confondus.')
                ->visible(fn (): bool => $this->activityPlanId !== null)
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.pages.partials.activity-presence-totals')
                        ->viewData(fn (): array => [
                            'totals' => $this->overviewTotals,
                            'activity' => $this->getOverview()['activity'] ?? null,
                        ]),
                ]),
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
                RetreatAtelier::query()
                    ->where('is_active', true)
                    ->whereHas('participants', fn (Builder $query): Builder => $query->where('is_active', true))
                    ->with('responsable')
                    ->orderBy('numero')
            )
            ->columns([
                TextColumn::make('numero')
                    ->label('Atelier')
                    ->formatStateUsing(fn (int $state): string => 'n°'.$state)
                    ->sortable(),
                TextColumn::make('age_range')
                    ->label('Tranche d\'âge')
                    ->state(function (RetreatAtelier $record): string {
                        return app(\App\Services\RetreatPlacementAssignmentService::class)
                            ->describeAtelierAgeRange($record);
                    }),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->placeholder('—'),
                TextColumn::make('stats_participants')
                    ->label('Participants')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['participants']),
                TextColumn::make('stats_present')
                    ->label('Présents')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['present'])
                    ->badge()
                    ->color('success'),
                TextColumn::make('stats_late')
                    ->label('Retards')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['late'])
                    ->badge()
                    ->color('warning'),
                TextColumn::make('stats_absent')
                    ->label('Absents')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['absent'])
                    ->badge()
                    ->color('danger'),
                TextColumn::make('stats_excused')
                    ->label('Excusés')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['excused'])
                    ->badge()
                    ->color('info'),
                TextColumn::make('stats_unmarked')
                    ->label('Non pointés')
                    ->state(fn (RetreatAtelier $record): int => $this->statsForAtelier($record->id)['unmarked'])
                    ->badge()
                    ->color('gray'),
                TextColumn::make('stats_rate')
                    ->label('Taux présence')
                    ->state(fn (RetreatAtelier $record): string => $this->statsForAtelier($record->id)['present_rate'].' %')
                    ->color('primary'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Aucun atelier avec participants')
            ->emptyStateDescription('Affectez des participants aux ateliers pour voir la synthèse.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOverview(): array
    {
        if (! $this->activityPlanId) {
            return [
                'totals' => [],
                'rows' => [],
            ];
        }

        if ($this->overviewCache === null) {
            $this->overviewCache = app(RetreatActivityPresenceOverviewService::class)
                ->buildForActivity((int) $this->activityPlanId);
        }

        return $this->overviewCache;
    }

    /**
     * @return void
     */
    protected function refreshOverviewTotals(): void
    {
        $this->overviewTotals = $this->getOverview()['totals'] ?? [];
    }

    /**
     * @param int $atelierId Identifiant atelier
     * @return array{participants: int, present: int, late: int, absent: int, excused: int, unmarked: int, present_rate: float|string}
     */
    protected function statsForAtelier(int $atelierId): array
    {
        foreach ($this->getOverview()['rows'] as $row) {
            if ((int) $row['atelier_id'] === $atelierId) {
                return $row;
            }
        }

        return [
            'participants' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'excused' => 0,
            'unmarked' => 0,
            'present_rate' => 0,
        ];
    }
}

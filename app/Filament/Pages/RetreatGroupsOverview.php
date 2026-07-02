<?php

namespace App\Filament\Pages;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Support\RetreatActiveEventScope;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Vue consolidée Chambres / Ateliers avec un affichage orienté opérationnel.
 */
class RetreatGroupsOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Répartition groupes';

    protected static ?string $title = 'Répartition des participants';

    protected static string|UnitEnum|null $navigationGroup = 'Logistique';

    protected static ?int $navigationSort = 65;

    protected static ?string $slug = 'repartition-groupes';

    protected string $view = 'filament.pages.retreat-groups-overview';

    /**
     * @return string|null
     */
    public function getSubheading(): ?string
    {
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event === null) {
            return 'Aucune retraite opérationnelle : seules les fiches de l\'édition courante sont affichées ici.';
        }

        return sprintf('Édition affichée : « %s » (ateliers et chambres de cette retraite uniquement).', $event->name);
    }

    /**
     * Cache local des chambres pour limiter les requêtes.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $chambresCache = null;

    /**
     * Cache local des ateliers pour limiter les requêtes.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $ateliersCache = null;

    /**
     * Données Chambres structurées pour la vue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function chambresData(): array
    {
        if ($this->chambresCache !== null) {
            return $this->chambresCache;
        }

        $atelierMap = $this->ateliersMap();

        $this->chambresCache = $this->scopedChambresQuery()
            ->with([
                'responsable:id,name',
                'event:id,name',
                'participants' => fn ($query) => $this->scopeParticipantsQuery($query),
            ])
            ->withCount([
                'participants as participants_count' => fn ($query) => $this->scopeParticipantsQuery($query),
            ])
            ->orderBy('nom')
            ->get()
            ->map(function (RetreatChambre $chambre) use ($atelierMap): array {
                $participants = $chambre->participants->map(function ($participant) use ($atelierMap): array {
                    $atelier = $participant->atelier_id ? ($atelierMap[$participant->atelier_id] ?? null) : null;

                    return [
                        'id' => (int) $participant->id,
                        'name' => trim(($participant->prenom ?? '').' '.($participant->nom ?? '')),
                        'role' => (string) ($participant->role_participant ?? ''),
                        'atelier_number' => $atelier['numero'] ?? null,
                    ];
                })->values()->all();

                return [
                    'id' => (int) $chambre->id,
                    'nom' => (string) ($chambre->nom ?? '—'),
                    'sexe' => (string) ($chambre->sexe ?? 'mixte'),
                    'capacite' => (int) ($chambre->capacite ?? 0),
                    'participants_count' => (int) ($chambre->participants_count ?? 0),
                    'responsable_name' => $chambre->responsable?->name,
                    'participants' => $participants,
                ];
            })
            ->values()
            ->all();

        return $this->chambresCache;
    }

    /**
     * Données Ateliers structurées pour la vue.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ateliersData(): array
    {
        if ($this->ateliersCache !== null) {
            return $this->ateliersCache;
        }

        $chambreMap = $this->chambresMap();

        $this->ateliersCache = $this->scopedAteliersQuery()
            ->with([
                'responsable:id,name',
                'adjoint:id,name',
                'event:id,name',
                'participants' => fn ($query) => $this->scopeParticipantsQuery($query),
            ])
            ->withCount([
                'participants as participants_count' => fn ($query) => $this->scopeParticipantsQuery($query),
            ])
            ->orderBy('age_min')
            ->orderBy('numero')
            ->get()
            ->map(function (RetreatAtelier $atelier) use ($chambreMap): array {
                $participants = $atelier->participants->map(function ($participant) use ($chambreMap): array {
                    $chambre = $participant->chambre_id ? ($chambreMap[$participant->chambre_id] ?? null) : null;

                    return [
                        'id' => (int) $participant->id,
                        'name' => trim(($participant->prenom ?? '').' '.($participant->nom ?? '')),
                        'role' => (string) ($participant->role_participant ?? ''),
                        'chambre_nom' => $chambre['nom'] ?? null,
                    ];
                })->values()->all();

                return [
                    'id' => (int) $atelier->id,
                    'numero' => (int) ($atelier->numero ?? 0),
                    'age_min' => $atelier->age_min,
                    'age_max' => $atelier->age_max,
                    'tranche_label' => $this->formatTrancheLabel($atelier->age_min, $atelier->age_max),
                    'participants_count' => (int) ($atelier->participants_count ?? 0),
                    'responsable_name' => $atelier->responsable?->name,
                    'adjoint_name' => $atelier->adjoint?->name,
                    'participants' => $participants,
                ];
            })
            ->values()
            ->all();

        return $this->ateliersCache;
    }

    /**
     * Statistiques globales de la page.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $chambres = collect($this->chambresData());
        $ateliers = collect($this->ateliersData());

        $hommes = $chambres->filter(fn (array $chambre): bool => $chambre['sexe'] === 'homme');
        $femmes = $chambres->filter(fn (array $chambre): bool => $chambre['sexe'] === 'femme');

        return [
            'total_participants' => (int) $chambres->sum('participants_count'),
            'hommes_current' => (int) $hommes->sum('participants_count'),
            'hommes_capacity' => (int) $hommes->sum('capacite'),
            'femmes_current' => (int) $femmes->sum('participants_count'),
            'femmes_capacity' => (int) $femmes->sum('capacite'),
            'ateliers_count' => (int) $ateliers->count(),
        ];
    }

    /**
     * Chambres filtrées par sexe.
     *
     * @param string $sexe
     * @return array<int, array<string, mixed>>
     */
    public function chambresBySexe(string $sexe): array
    {
        return collect($this->chambresData())
            ->filter(fn (array $chambre): bool => $chambre['sexe'] === $sexe)
            ->values()
            ->all();
    }

    /**
     * Ateliers groupés par tranche d'âge.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function ateliersByTranche(): array
    {
        return collect($this->ateliersData())
            ->groupBy('tranche_label')
            ->map(fn (Collection $items): array => $items->values()->all())
            ->all();
    }

    /**
     * Label de tranche d'âge lisible.
     *
     * @param int|null $ageMin
     * @param int|null $ageMax
     * @return string
     */
    private function formatTrancheLabel(?int $ageMin, ?int $ageMax): string
    {
        if ($ageMin !== null && $ageMax !== null) {
            return "{$ageMin}-{$ageMax} ans";
        }

        if ($ageMin !== null) {
            return "{$ageMin}+ ans";
        }

        if ($ageMax !== null) {
            return "≤ {$ageMax} ans";
        }

        return 'Tranche non définie';
    }

    /**
     * Mapping rapide des ateliers par identifiant.
     *
     * @return array<int, array{numero:int|null}>
     */
    private function ateliersMap(): array
    {
        return $this->scopedAteliersQuery()
            ->select(['id', 'numero'])
            ->get()
            ->mapWithKeys(fn (RetreatAtelier $atelier): array => [
                (int) $atelier->id => ['numero' => $atelier->numero ? (int) $atelier->numero : null],
            ])
            ->all();
    }

    /**
     * Mapping rapide des chambres par identifiant.
     *
     * @return array<int, array{nom:string|null}>
     */
    private function chambresMap(): array
    {
        return $this->scopedChambresQuery()
            ->select(['id', 'nom'])
            ->get()
            ->mapWithKeys(fn (RetreatChambre $chambre): array => [
                (int) $chambre->id => ['nom' => $chambre->nom ? (string) $chambre->nom : null],
            ])
            ->all();
    }

    /**
     * Chambres de l'édition opérationnelle courante (évite les doublons entre retraites).
     *
     * @return Builder<RetreatChambre>
     */
    private function scopedChambresQuery(): Builder
    {
        $query = RetreatActiveEventScope::applyToChambres(RetreatChambre::query());
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event !== null) {
            $query->where('event_id', $event->getKey());
        }

        return $query;
    }

    /**
     * Ateliers de l'édition opérationnelle courante (évite les doublons entre retraites).
     *
     * @return Builder<RetreatAtelier>
     */
    private function scopedAteliersQuery(): Builder
    {
        $query = RetreatActiveEventScope::applyToAteliers(RetreatAtelier::query());
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event !== null) {
            $query->where('event_id', $event->getKey());
        }

        return $query;
    }

    /**
     * Participants visibles dans les vues opérationnelles.
     *
     * @param  Builder<\App\Models\RetreatParticipant>  $query
     * @return Builder<\App\Models\RetreatParticipant>
     */
    private function scopeParticipantsQuery(Builder $query): Builder
    {
        $query = RetreatActiveEventScope::applyToParticipants($query);
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($event !== null) {
            $query->where('event_id', $event->getKey());
        }

        return $query;
    }
}


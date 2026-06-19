<?php

namespace App\Services;

use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use Illuminate\Support\Collection;

/**
 * Propose des ateliers compatibles ou la création d'un nouvel atelier pour un participant en quarantaine.
 */
class RetreatAtelierProposalService
{
    public function __construct(
        protected RetreatPlacementAssignmentService $placement,
    ) {}

    /**
     * Construit les propositions d'affectation pour un participant en quarantaine.
     *
     * @param RetreatParticipant $participant Participant en quarantaine
     * @return array{
     *     eligible: list<array{
     *         atelier_id: int,
     *         numero: int,
     *         label: string,
     *         participants_count: int,
     *         age_range: string,
     *         score: float,
     *         recommended: bool
     *     }>,
     *     recommended_atelier_id: int|null,
     *     creation_suggestion: array{
     *         suggested_numero: int,
     *         suggested_age_min: int,
     *         suggested_age_max: int,
     *         label: string,
     *         reason: string
     *     }|null,
     *     summary: string
     * }
     */
    public function buildForParticipant(RetreatParticipant $participant): array
    {
        $participant->refresh();
        $ranked = $this->placement->rankEligibleAteliersForParticipant($participant);

        $recommendedId = $ranked->first()['atelier_id'] ?? null;

        $eligible = $ranked->map(function (array $row, int $index) use ($recommendedId): array {
            /** @var RetreatAtelier $atelier */
            $atelier = $row['atelier'];

            return [
                'atelier_id' => (int) $atelier->id,
                'numero' => (int) $atelier->numero,
                'label' => sprintf(
                    'Atelier n°%s — %s (%d participant(s))',
                    $atelier->numero,
                    $this->placement->describeAtelierAgeRange($atelier),
                    (int) ($atelier->participants_count ?? $atelier->participants()->count()),
                ),
                'participants_count' => (int) ($atelier->participants_count ?? 0),
                'age_range' => $this->placement->describeAtelierAgeRange($atelier),
                'score' => (float) $row['score'],
                'recommended' => $recommendedId !== null && (int) $atelier->id === (int) $recommendedId && $index === 0,
            ];
        })->values()->all();

        $creationSuggestion = $eligible === []
            ? $this->suggestNewAtelierCreation((int) $participant->age)
            : null;

        $summary = match (true) {
            $eligible !== [] && $creationSuggestion === null => sprintf(
                '%d atelier(s) compatible(s) — recommandation : n°%s',
                count($eligible),
                $ranked->first()['atelier']->numero ?? '?',
            ),
            $creationSuggestion !== null => 'Aucun atelier compatible — création suggérée',
            default => 'Aucune proposition disponible',
        };

        return [
            'eligible' => $eligible,
            'recommended_atelier_id' => $recommendedId,
            'creation_suggestion' => $creationSuggestion,
            'summary' => $summary,
        ];
    }

    /**
     * Résumé court pour affichage en colonne de liste.
     *
     * @param RetreatParticipant $participant Participant
     * @return string Libellé
     */
    public function summaryForParticipant(RetreatParticipant $participant): string
    {
        return $this->buildForParticipant($participant)['summary'];
    }

    /**
     * Suggère un nouvel atelier lorsqu'aucun existant ne convient.
     *
     * @param int $age Âge du participant
     * @return array{
     *     suggested_numero: int,
     *     suggested_age_min: int,
     *     suggested_age_max: int,
     *     label: string,
     *     reason: string
     * }
     */
    public function suggestNewAtelierCreation(int $age): array
    {
        [$ageMin, $ageMax] = $this->ageBoundsForAge($age);

        $maxNumero = (int) (RetreatAtelier::query()->max('numero') ?? 0);
        $suggestedNumero = $maxNumero + 1;

        return [
            'suggested_numero' => $suggestedNumero,
            'suggested_age_min' => $ageMin,
            'suggested_age_max' => $ageMax,
            'label' => sprintf('Créer atelier n°%d (%d – %d ans)', $suggestedNumero, $ageMin, $ageMax),
            'reason' => sprintf(
                'Aucun atelier actif ne couvre l\'âge de %d ans. Un nouvel atelier pour la tranche %d – %d ans est suggéré.',
                $age,
                $ageMin,
                $ageMax,
            ),
        ];
    }

    /**
     * @param int $age Âge en années
     * @return array{0: int, 1: int} Bornes min et max de tranche
     */
    protected function ageBoundsForAge(int $age): array
    {
        $band = $this->placement->ageBand($age);

        return match ($band) {
            '20-24' => [20, 24],
            '25-29' => [25, 29],
            '30+' => [30, 99],
            default => [15, 19],
        };
    }
}

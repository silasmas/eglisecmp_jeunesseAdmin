<?php

namespace App\Services;

use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use Illuminate\Support\Facades\DB;

/**
 * Reconduit ateliers et chambres d'une retraite source vers une nouvelle édition.
 */
class RetreatLogisticsReplicationService
{
    /**
     * Copie les fiches logistiques de la retraite source vers la cible (par event_id).
     *
     * @param  ChurchEvent  $source Retraite de référence
     * @param  ChurchEvent  $target Nouvelle retraite opérationnelle
     * @return array{
     *   ateliers_created: int,
     *   ateliers_reused: int,
     *   chambres_created: int,
     *   chambres_reused: int
     * }
     */
    public function replicateFromEvent(ChurchEvent $source, ChurchEvent $target): array
    {
        if ($target->isArchived() || $target->isPublicPortalClosed()) {
            throw new \InvalidArgumentException('Impossible de reconduire vers un événement clôturé ou archivé.');
        }

        $stats = [
            'ateliers_created' => 0,
            'ateliers_reused' => 0,
            'chambres_created' => 0,
            'chambres_reused' => 0,
        ];

        DB::transaction(function () use ($source, $target, &$stats): void {
            foreach (RetreatAtelier::query()->where('event_id', $source->getKey())->get() as $atelier) {
                $result = $this->ensureAtelier($atelier, $target);
                $stats[$result === 'created' ? 'ateliers_created' : 'ateliers_reused']++;
            }

            foreach (RetreatChambre::query()->where('event_id', $source->getKey())->get() as $chambre) {
                $result = $this->ensureChambre($chambre, $target);
                $stats[$result === 'created' ? 'chambres_created' : 'chambres_reused']++;
            }
        });

        return $stats;
    }

    /**
     * Crée l'atelier sur la cible s'il n'existe pas déjà pour cet événement.
     *
     * @param  RetreatAtelier  $template Modèle issu de la retraite source
     * @param  ChurchEvent  $target Événement cible
     * @return string created|reused
     */
    private function ensureAtelier(RetreatAtelier $template, ChurchEvent $target): string
    {
        $existing = RetreatAtelier::query()
            ->where('event_id', $target->getKey())
            ->where('numero', $template->numero)
            ->where('responsable_user_id', $template->responsable_user_id)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return 'reused';
        }

        RetreatAtelier::query()->create([
            'event_id' => $target->getKey(),
            'numero' => $template->numero,
            'age_min' => $template->age_min,
            'age_max' => $template->age_max,
            'responsable_user_id' => $template->responsable_user_id,
            'adjoint_user_id' => $template->adjoint_user_id,
            'role_on_atelier' => $template->role_on_atelier,
            'description' => $template->description,
            'is_active' => true,
        ]);

        return 'created';
    }

    /**
     * Crée la chambre sur la cible si elle n'existe pas déjà pour cet événement.
     *
     * @param  RetreatChambre  $template Modèle issu de la retraite source
     * @param  ChurchEvent  $target Événement cible
     * @return string created|reused
     */
    private function ensureChambre(RetreatChambre $template, ChurchEvent $target): string
    {
        $existing = RetreatChambre::query()
            ->where('event_id', $target->getKey())
            ->where('nom', $template->nom)
            ->where('sexe', $template->sexe)
            ->where('responsable_user_id', $template->responsable_user_id)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return 'reused';
        }

        RetreatChambre::query()->create([
            'event_id' => $target->getKey(),
            'nom' => $template->nom,
            'capacite' => $template->capacite,
            'sexe' => $template->sexe,
            'responsable_user_id' => $template->responsable_user_id,
            'role_on_chambre' => $template->role_on_chambre,
            'description' => $template->description,
            'is_active' => true,
        ]);

        return 'created';
    }

    /**
     * Message utilisateur après reconduction.
     *
     * @param  array<string, int>  $stats Statistiques de reconduction
     * @return string
     */
    public function summaryMessage(array $stats): string
    {
        $parts = [];

        if ($stats['ateliers_created'] > 0) {
            $parts[] = "{$stats['ateliers_created']} atelier(s) créé(s)";
        }
        if ($stats['ateliers_reused'] > 0) {
            $parts[] = "{$stats['ateliers_reused']} atelier(s) déjà disponible(s)";
        }
        if ($stats['chambres_created'] > 0) {
            $parts[] = "{$stats['chambres_created']} chambre(s) créée(s)";
        }
        if ($stats['chambres_reused'] > 0) {
            $parts[] = "{$stats['chambres_reused']} chambre(s) déjà disponible(s)";
        }

        if ($parts === []) {
            return 'Aucun atelier ni chambre trouvé sur la retraite source.';
        }

        return implode(' · ', $parts).'.';
    }

    /**
     * Liste les retraites éligibles comme source (avec logistique).
     *
     * @return array<int, string> id => label
     */
    public function sourceEventOptions(?int $excludeEventId = null): array
    {
        return ChurchEvent::query()
            ->when($excludeEventId, fn ($q) => $q->whereKeyNot($excludeEventId))
            ->where(function ($query): void {
                $query->whereHas('ateliers')
                    ->orWhereHas('chambres')
                    ->orWhereHas('participants');
            })
            ->orderByDesc('start_at')
            ->pluck('name', 'id')
            ->all();
    }
}

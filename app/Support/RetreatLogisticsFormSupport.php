<?php

namespace App\Support;

use App\Enums\ChurchEventType;
use App\Models\ChurchEvent;
use Illuminate\Validation\ValidationException;

/**
 * Aide à la saisie admin : rattachement ateliers/chambres à une retraite opérationnelle.
 */
final class RetreatLogisticsFormSupport
{
    /**
     * Retraites éligibles pour créer une chambre ou un atelier.
     *
     * @return array<int, string> id => libellé
     */
    public function operationalEventOptions(): array
    {
        return RetreatActiveEventScope::operationalEvents(
            ChurchEvent::query()
                ->where('type', ChurchEventType::Retraite->value)
                ->orderByDesc('is_active')
                ->orderByDesc('start_at')
        )
            ->get()
            ->mapWithKeys(fn (ChurchEvent $event): array => [
                $event->getKey() => $this->formatEventOptionLabel($event),
            ])
            ->all();
    }

    /**
     * Prépare les données avant création (page ou modale Filament).
     *
     * @param  array<string, mixed>  $data Données du formulaire
     * @return array<string, mixed>
     */
    public function prepareCreateData(array $data): array
    {
        $eventId = isset($data['event_id']) ? (int) $data['event_id'] : null;
        $event = $this->resolveEventForAssignment($eventId);

        if ($event === null) {
            throw ValidationException::withMessages([
                'event_id' => 'Sélectionnez une retraite opérationnelle (non archivée, accès public ouvert).',
            ]);
        }

        $data['event_id'] = $event->getKey();
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    /**
     * @param  int|null  $eventId Identifiant choisi dans le formulaire
     * @return ChurchEvent|null
     */
    public function resolveEventForAssignment(?int $eventId = null): ?ChurchEvent
    {
        if ($eventId !== null) {
            $selected = ChurchEvent::query()->find($eventId);
            if ($selected !== null && $selected->isOperationalForLogistics()) {
                return $selected;
            }
        }

        return ChurchEvent::resolveOperationalLogisticsEvent();
    }

    /**
     * Message affiché au-dessus des listes ateliers/chambres.
     *
     * @return string|null
     */
    public function listContextMessage(): ?string
    {
        $options = $this->operationalEventOptions();

        if ($options === []) {
            return 'Aucune retraite opérationnelle : créez ou restaurez un événement non clôturé et non archivé, puis activez-le si besoin.';
        }

        $default = ChurchEvent::resolveOperationalLogisticsEvent();

        if ($default === null) {
            return 'Sélectionnez la retraite cible lors de la création d\'une chambre ou d\'un atelier.';
        }

        return sprintf(
            'Les nouvelles fiches sont rattachées à la retraite « %s » (événement actif et opérationnel par défaut).',
            $default->name,
        );
    }

    /**
     * @param  ChurchEvent  $event Événement retraite
     * @return string
     */
    private function formatEventOptionLabel(ChurchEvent $event): string
    {
        $active = $event->is_active ? 'actif' : 'inactif';
        $period = $event->start_at?->format('d/m/Y') ?? '—';

        return sprintf('%s · %s · %s', $event->name, $active, $period);
    }
}

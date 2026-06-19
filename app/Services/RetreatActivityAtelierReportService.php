<?php

namespace App\Services;

use App\Models\RetreatActivityAtelierReport;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Compte-rendu d'activité par atelier (partagé admin Filament et portail public).
 */
class RetreatActivityAtelierReportService
{
    /**
     * Options du sélecteur « conducteur du débat ».
     *
     * @param RetreatAtelier $atelier Atelier
     * @param Collection<int, RetreatParticipant> $participants Participants de l'atelier
     * @return list<array{key: string, label: string}>
     */
    public function buildDebatOptions(RetreatAtelier $atelier, Collection $participants): array
    {
        $options = [];

        if ($atelier->responsable) {
            $options[] = [
                'key' => 'user:'.$atelier->responsable->id,
                'label' => $atelier->responsable->name.' (Responsable)',
            ];
        }

        if ($atelier->adjoint) {
            $options[] = [
                'key' => 'user:'.$atelier->adjoint->id,
                'label' => $atelier->adjoint->name.' (Adjoint)',
            ];
        }

        foreach ($participants as $participant) {
            $options[] = [
                'key' => 'participant:'.$participant->id,
                'label' => $participant->full_name,
            ];
        }

        return $options;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function workerOptionsForPortal(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param RetreatActivityAtelierReport|null $report Compte-rendu existant
     * @return array{
     *     locked: bool,
     *     submitted_at: string|null,
     *     submitted_by: string|null,
     *     sujet: string,
     *     texte_biblique: string,
     *     resume: string,
     *     conducteur_user_ids: list<int>,
     *     conducteur_participant_ids: list<int>,
     *     conducteur_debat_keys: list<string>
     * }
     */
    public function serializeReportForm(?RetreatActivityAtelierReport $report): array
    {
        $userIds = [];
        $participantIds = [];
        $debatKeys = [];

        if ($report && is_array($report->conducteurs)) {
            foreach ($report->conducteurs as $conducteur) {
                $type = $conducteur['type'] ?? '';
                $id = isset($conducteur['id']) ? (int) $conducteur['id'] : null;

                if ($type === 'user' && $id) {
                    $userIds[] = $id;
                }
                if ($type === 'participant' && $id) {
                    $participantIds[] = $id;
                }
                if ($type === 'debat_user' && $id) {
                    $debatKeys[] = 'user:'.$id;
                }
                if ($type === 'debat_participant' && $id) {
                    $debatKeys[] = 'participant:'.$id;
                }
            }
        }

        return [
            'locked' => $report?->isSubmitted() ?? false,
            'submitted_at' => $report?->submitted_at?->format('d/m/Y à H:i'),
            'submitted_by' => $report?->recorder?->name,
            'sujet' => (string) ($report?->sujet ?? ''),
            'texte_biblique' => (string) ($report?->texte_biblique ?? ''),
            'resume' => (string) ($report?->resume ?? ''),
            'conducteur_user_ids' => $userIds,
            'conducteur_participant_ids' => $participantIds,
            'conducteur_debat_keys' => $debatKeys,
        ];
    }

    /**
     * Soumet définitivement le compte-rendu d'un atelier pour une activité.
     *
     * @param User $user Utilisateur soumettant
     * @param int $activityPlanId Plan d'activité
     * @param int $atelierId Atelier
     * @param array<string, mixed> $data Données du formulaire
     * @return array{success: bool, message: string, report?: array<string, mixed>}
     */
    public function submitReport(User $user, int $activityPlanId, int $atelierId, array $data): array
    {
        $auth = app(RetreatAtelierAuthorizationService::class);
        $atelier = RetreatAtelier::query()->findOrFail($atelierId);

        if (! $auth->canManageAtelier($user, $atelier)) {
            return [
                'success' => false,
                'message' => 'Seuls le responsable ou l\'adjoint peuvent soumettre le compte-rendu.',
            ];
        }

        $existing = RetreatActivityAtelierReport::query()
            ->where('activity_plan_id', $activityPlanId)
            ->where('atelier_id', $atelierId)
            ->first();

        if ($existing?->isSubmitted()) {
            return [
                'success' => false,
                'message' => 'Ce compte-rendu a déjà été soumis et ne peut plus être modifié.',
            ];
        }

        if (blank($data['sujet'] ?? null)) {
            return [
                'success' => false,
                'message' => 'Le sujet est obligatoire avant la soumission.',
            ];
        }

        $conducteurs = $this->buildConducteursPayload(
            (array) ($data['conducteur_user_ids'] ?? []),
            (array) ($data['conducteur_participant_ids'] ?? []),
            (array) ($data['conducteur_debat_keys'] ?? []),
        );

        $activityPlan = RetreatActivityPlan::query()
            ->with(['session.event'])
            ->findOrFail($activityPlanId);

        $report = RetreatActivityAtelierReport::query()->updateOrCreate(
            [
                'activity_plan_id' => $activityPlanId,
                'atelier_id' => $atelierId,
            ],
            [
                'sujet' => (string) $data['sujet'],
                'texte_biblique' => filled($data['texte_biblique'] ?? null) ? (string) $data['texte_biblique'] : null,
                'conducteurs' => $conducteurs,
                'resume' => filled($data['resume'] ?? null) ? (string) $data['resume'] : null,
                'recorded_by' => $user->id,
                'submitted_at' => now(),
                'is_active' => true,
            ]
        );

        $report->load('recorder');

        try {
            app(RetreatActivityAtelierReportNotifier::class)->notifySubmitted(
                $report->fresh(),
                $activityPlan,
                $atelier,
                $user
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [
            'success' => true,
            'message' => sprintf('Compte-rendu de l\'atelier n°%s soumis avec succès.', $atelier->numero),
            'report' => $this->serializeReportForm($report->fresh(['recorder'])),
        ];
    }

    /**
     * @param array<int, int|string> $userIds
     * @param array<int, int|string> $participantIds
     * @param array<int, string> $debatKeys
     * @return list<array{type: string, id: int, label: string}>
     */
    public function buildConducteursPayload(array $userIds, array $participantIds, array $debatKeys = []): array
    {
        $conducteurs = [];

        foreach (array_unique(array_filter(array_map('intval', $userIds))) as $userId) {
            $worker = User::query()->find($userId);
            if ($worker) {
                $conducteurs[] = [
                    'type' => 'user',
                    'id' => $worker->id,
                    'label' => $worker->name,
                ];
            }
        }

        foreach (array_unique(array_filter(array_map('intval', $participantIds))) as $participantId) {
            $participant = RetreatParticipant::query()->find($participantId);
            if ($participant) {
                $conducteurs[] = [
                    'type' => 'participant',
                    'id' => $participant->id,
                    'label' => $participant->full_name,
                ];
            }
        }

        foreach (array_unique(array_filter($debatKeys)) as $key) {
            if (str_starts_with($key, 'user:')) {
                $userId = (int) substr($key, 5);
                $worker = User::query()->find($userId);
                if ($worker) {
                    $conducteurs[] = [
                        'type' => 'debat_user',
                        'id' => $worker->id,
                        'label' => $worker->name,
                    ];
                }
            }

            if (str_starts_with($key, 'participant:')) {
                $participantId = (int) substr($key, 12);
                $participant = RetreatParticipant::query()->find($participantId);
                if ($participant) {
                    $conducteurs[] = [
                        'type' => 'debat_participant',
                        'id' => $participant->id,
                        'label' => $participant->full_name,
                    ];
                }
            }
        }

        return $conducteurs;
    }
}

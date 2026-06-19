<?php

namespace App\Services;

use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityAtelierReport;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Construit les données de pointage par atelier et enregistre les présences.
 */
class RetreatAtelierAttendancePanelService
{
    public function __construct(
        protected RetreatAtelierAuthorizationService $auth,
        protected RetreatActivityPlanScheduleService $scheduleService,
        protected RetreatActivityAtelierReportService $reportService,
    ) {}

    /**
     * @return array<int, string>
     */
    public function activityOptionsForUser(?User $user): array
    {
        return RetreatActivityPlan::query()
            ->where('is_active', true)
            ->with('session.event')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (RetreatActivityPlan $plan): array => [
                $plan->id => $this->formatActivityLabel($plan),
            ])
            ->all();
    }

    /**
     * Construit les blocs atelier pour une activité et un utilisateur donné.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildBlocksForUser(?User $user, int $activityPlanId): array
    {
        $atelierQuery = RetreatAtelier::query()
            ->where('is_active', true)
            ->with(['responsable', 'adjoint'])
            ->orderBy('numero');

        if (! $this->auth->isSuperAdmin($user)) {
            $managedIds = $this->auth->managedAtelierIds($user);
            if ($managedIds->isEmpty()) {
                return [];
            }
            $atelierQuery->whereIn('id', $managedIds);
        }

        $blocks = [];

        foreach ($atelierQuery->get() as $atelier) {
            $participants = RetreatParticipant::query()
                ->where('atelier_id', $atelier->id)
                ->where('is_active', true)
                ->orderBy('prenom')
                ->orderBy('nom')
                ->get();

            if ($participants->isEmpty()) {
                continue;
            }

            $attendances = RetreatActivityAttendance::query()
                ->where('activity_plan_id', $activityPlanId)
                ->whereIn('participant_id', $participants->pluck('id'))
                ->with('recorder')
                ->get()
                ->keyBy('participant_id');

            $report = RetreatActivityAtelierReport::query()
                ->where('activity_plan_id', $activityPlanId)
                ->where('atelier_id', $atelier->id)
                ->with('recorder')
                ->first();

            $blocks[] = [
                'atelier' => $atelier,
                'can_manage' => $this->auth->canManageAtelier($user, $atelier),
                'participants' => $participants,
                'attendances' => $attendances,
                'report' => $report,
                'debat_options' => $this->reportService->buildDebatOptions($atelier, $participants),
            ];
        }

        return $blocks;
    }

    /**
     * Sérialise les blocs atelier pour une réponse JSON (portail ouvrier).
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    public function serializeBlocksForPortal(array $blocks): array
    {
        $payload = [];
        $workerOptions = $this->reportService->workerOptionsForPortal();

        foreach ($blocks as $block) {
            /** @var RetreatAtelier $atelier */
            $atelier = $block['atelier'];
            /** @var Collection<int, RetreatParticipant> $participants */
            $participants = $block['participants'];
            /** @var Collection<int, RetreatActivityAttendance> $attendances */
            $attendances = $block['attendances'];
            /** @var RetreatActivityAtelierReport|null $report */
            $report = $block['report'] ?? null;

            $participantRows = [];

            foreach ($participants as $index => $participant) {
                $attendance = $attendances->get($participant->id);
                $participantRows[] = [
                    'id' => $participant->id,
                    'full_name' => $participant->full_name,
                    'number' => $index + 1,
                    'status' => $attendance?->status ?? 'absent',
                    'excuse_note' => $attendance?->note,
                    'recorded_by' => $attendance?->recorder?->name,
                    'recorded_at' => $attendance?->updated_at?->format('d/m H:i'),
                ];
            }

            $presentCount = collect($participantRows)
                ->filter(fn (array $row): bool => in_array($row['status'], ['present', 'late'], true))
                ->count();

            $canManage = (bool) ($block['can_manage'] ?? false);

            $payload[] = [
                'atelier_id' => $atelier->id,
                'atelier_numero' => $atelier->numero,
                'responsable' => $atelier->responsable?->name,
                'adjoint' => $atelier->adjoint?->name,
                'can_manage' => $canManage,
                'participants_count' => count($participantRows),
                'present_count' => $presentCount,
                'participants' => $participantRows,
                'participant_options' => $participants->map(fn (RetreatParticipant $participant): array => [
                    'id' => (int) $participant->id,
                    'name' => $participant->full_name,
                ])->values()->all(),
                'debat_options' => $block['debat_options'] ?? [],
                'worker_options' => $workerOptions,
                'report' => $this->reportService->serializeReportForm($report),
            ];
        }

        return $payload;
    }

    /**
     * Compte les présents/retards pour un atelier et une activité.
     */
    public function countPresentForAtelier(int $activityPlanId, int $atelierId): int
    {
        $participantIds = RetreatParticipant::query()
            ->where('atelier_id', $atelierId)
            ->where('is_active', true)
            ->pluck('id');

        if ($participantIds->isEmpty()) {
            return 0;
        }

        return RetreatActivityAttendance::query()
            ->where('activity_plan_id', $activityPlanId)
            ->whereIn('participant_id', $participantIds)
            ->whereIn('status', ['present', 'late'])
            ->count();
    }

    /**
     * Enregistre ou met à jour la présence d'un participant.
     *
     * @return array{success: bool, message: string}
     */
    public function setAttendance(
        ?User $user,
        int $activityPlanId,
        int $participantId,
        string $status,
        ?string $excuseNote = null,
    ): array {
        $participant = RetreatParticipant::query()->with('atelier')->findOrFail($participantId);

        if (! $this->auth->canManageParticipant($user, $participant)) {
            return [
                'success' => false,
                'message' => 'Seuls le responsable ou l\'adjoint de l\'atelier peuvent marquer la présence.',
            ];
        }

        $activityPlan = RetreatActivityPlan::query()->findOrFail($activityPlanId);

        if (! $this->auth->isSuperAdmin($user) && ! $this->scheduleService->isAttendanceWindowOpen($activityPlan)) {
            return [
                'success' => false,
                'message' => __('retraite.attendance_window_closed'),
            ];
        }

        $allowed = ['present', 'absent', 'late', 'excused'];
        if (! in_array($status, $allowed, true)) {
            return [
                'success' => false,
                'message' => 'Statut de présence invalide.',
            ];
        }

        $note = null;
        if ($status === 'excused') {
            $note = filled($excuseNote) ? trim($excuseNote) : null;
        }

        RetreatActivityAttendance::query()->updateOrCreate(
            [
                'activity_plan_id' => $activityPlanId,
                'participant_id' => $participantId,
            ],
            [
                'status' => $status,
                'check_in_at' => in_array($status, ['present', 'late'], true) ? now() : null,
                'scan_source' => 'manual',
                'recorded_by' => $user?->id,
                'note' => $note,
                'is_active' => true,
            ]
        );

        if (in_array($status, ['present', 'late'], true)) {
            $participant->update([
                'present' => true,
                'date_presence' => $participant->date_presence ?? now(),
            ]);
        }

        $attendance = RetreatActivityAttendance::query()
            ->where('activity_plan_id', $activityPlanId)
            ->where('participant_id', $participantId)
            ->with('recorder')
            ->first();

        return [
            'success' => true,
            'message' => "Statut « {$status} » enregistré pour {$participant->full_name}.",
            'data' => [
                'participant_id' => (int) $participantId,
                'atelier_id' => (int) ($participant->atelier_id ?? 0),
                'status' => $status,
                'excuse_note' => $attendance?->note,
                'recorded_by' => $attendance?->recorder?->name ?? $user?->name,
                'recorded_at' => $attendance?->updated_at?->format('d/m H:i'),
                'present_count' => $participant->atelier_id
                    ? $this->countPresentForAtelier($activityPlanId, (int) $participant->atelier_id)
                    : 0,
            ],
        ];
    }

    /**
     * Enregistre le motif d'excuse d'un participant déjà marqué excusé.
     *
     * @return array{success: bool, message: string}
     */
    public function saveExcuseNote(
        ?User $user,
        int $activityPlanId,
        int $participantId,
        ?string $note,
    ): array {
        $participant = RetreatParticipant::query()->with('atelier')->findOrFail($participantId);

        if (! $this->auth->canManageParticipant($user, $participant)) {
            return [
                'success' => false,
                'message' => 'Action non autorisée.',
            ];
        }

        $activityPlan = RetreatActivityPlan::query()->findOrFail($activityPlanId);

        if (! $this->auth->isSuperAdmin($user) && ! $this->scheduleService->isAttendanceWindowOpen($activityPlan)) {
            return [
                'success' => false,
                'message' => __('retraite.attendance_window_closed'),
            ];
        }

        $attendance = RetreatActivityAttendance::query()
            ->where('activity_plan_id', $activityPlanId)
            ->where('participant_id', $participantId)
            ->first();

        if ($attendance?->status !== 'excused') {
            return [
                'success' => false,
                'message' => 'Le participant doit être marqué excusé avant d\'enregistrer un motif.',
            ];
        }

        $trimmedNote = filled($note) ? trim((string) $note) : null;

        $attendance->update([
            'note' => $trimmedNote,
            'recorded_by' => $user?->id,
        ]);

        $attendance->load('recorder');

        return [
            'success' => true,
            'message' => "Motif d'excuse enregistré pour {$participant->full_name}.",
            'data' => [
                'participant_id' => (int) $participantId,
                'atelier_id' => (int) ($participant->atelier_id ?? 0),
                'status' => 'excused',
                'excuse_note' => $trimmedNote,
                'recorded_by' => $attendance->recorder?->name ?? $user?->name,
                'recorded_at' => $attendance->updated_at?->format('d/m H:i'),
            ],
        ];
    }

    protected function formatActivityLabel(RetreatActivityPlan $plan): string
    {
        $sessionDate = $plan->session?->start_at?->format('d/m/Y') ?? '—';
        $eventName = $plan->session?->event?->name ?? 'Retraite';

        return "{$plan->title} · {$eventName} · {$sessionDate}";
    }
}

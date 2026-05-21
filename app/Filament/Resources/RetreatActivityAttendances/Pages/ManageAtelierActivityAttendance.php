<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Pages;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityAtelierReport;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatActivityAtelierReportNotifier;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Pointage des présences regroupé par atelier pour une activité donnée.
 */
class ManageAtelierActivityAttendance extends Page
{
    protected static string $resource = RetreatActivityAttendanceResource::class;

    protected static ?string $title = 'Pointage par atelier';

    protected static ?string $navigationLabel = 'Pointage par atelier';

    protected string $view = 'filament.resources.retreat-activity-attendances.pages.manage-atelier-attendance';

    public ?int $activityPlanId = null;

    /** @var array<int, array<string, mixed>> */
    public array $reportForms = [];

    /** @var array<int, array<string, mixed>> */
    public array $atelierBlocks = [];

    public bool $isLoadingAteliers = false;

    /** @var array<int, string> Motifs d'excuse par participant (clé = participant_id). */
    public array $excuseNotes = [];

    /**
     * Charge les données si une activité est déjà sélectionnée au montage.
     */
    public function mount(): void
    {
        if ($this->activityPlanId) {
            $this->reloadAtelierData();
        }
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('ViewAny:RetreatActivityAttendance')) {
            return true;
        }

        return app(RetreatAtelierAuthorizationService::class)->managesAnyAtelier($user);
    }

    /**
     * @return array<int, string>
     */
    public function getActivityOptionsProperty(): array
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
     * Liste des ouvriers actifs pour le sélecteur de conducteurs.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getWorkerOptionsProperty()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(200)
            ->get();
    }

    /**
     * Recharge les ateliers et les formulaires de compte-rendu lors du changement d'activité.
     */
    public function updatedActivityPlanId(): void
    {
        $this->isLoadingAteliers = true;
        $this->reloadAtelierData();
        $this->isLoadingAteliers = false;
    }

    /**
     * Charge les blocs atelier + compte-rendus pour l'activité sélectionnée.
     */
    public function reloadAtelierData(): void
    {
        $this->atelierBlocks = [];
        $this->reportForms = [];
        $this->excuseNotes = [];

        if (! $this->activityPlanId) {
            return;
        }

        $this->atelierBlocks = $this->buildAtelierBlocks();
        $this->loadReportFormsForActivity();
        $this->loadExcuseNotesFromAttendances();
    }

    /**
     * Enregistre ou met à jour la présence d'un participant.
     */
    public function setAttendance(int $participantId, string $status): void
    {
        if (! $this->activityPlanId) {
            return;
        }

        $participant = RetreatParticipant::query()->with('atelier')->findOrFail($participantId);
        $user = Auth::user();
        $auth = app(RetreatAtelierAuthorizationService::class);

        if (! $auth->canManageParticipant($user, $participant)) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Seuls le responsable ou l’adjoint de l’atelier peuvent marquer la présence.')
                ->danger()
                ->send();

            return;
        }

        $allowed = ['present', 'absent', 'late', 'excused'];
        if (! in_array($status, $allowed, true)) {
            return;
        }

        $note = null;
        if ($status === 'excused') {
            $note = filled($this->excuseNotes[$participantId] ?? null)
                ? trim((string) $this->excuseNotes[$participantId])
                : null;
        }

        RetreatActivityAttendance::query()->updateOrCreate(
            [
                'activity_plan_id' => $this->activityPlanId,
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

        if ($status === 'excused') {
            $this->excuseNotes[$participantId] = $note ?? '';
        } else {
            unset($this->excuseNotes[$participantId]);
        }

        if (in_array($status, ['present', 'late'], true)) {
            $participant->update([
                'present' => true,
                'date_presence' => $participant->date_presence ?? now(),
            ]);
        }

        $this->reloadAtelierData();

        Notification::make()
            ->title('Présence enregistrée')
            ->body("Statut « {$status} » enregistré pour {$participant->full_name}.")
            ->success()
            ->send();
    }

    /**
     * Enregistre le motif d'excuse d'un participant (statut excused requis).
     */
    public function saveExcuseNote(int $participantId): void
    {
        if (! $this->activityPlanId) {
            return;
        }

        $participant = RetreatParticipant::query()->with('atelier')->findOrFail($participantId);
        $user = Auth::user();
        $auth = app(RetreatAtelierAuthorizationService::class);

        if (! $auth->canManageParticipant($user, $participant)) {
            return;
        }

        $attendance = RetreatActivityAttendance::query()
            ->where('activity_plan_id', $this->activityPlanId)
            ->where('participant_id', $participantId)
            ->first();

        if ($attendance?->status !== 'excused') {
            return;
        }

        $note = trim($this->excuseNotes[$participantId] ?? '');

        $attendance->update([
            'note' => filled($note) ? $note : null,
            'recorded_by' => $user?->id,
        ]);

        Notification::make()
            ->title('Motif enregistré')
            ->body("Motif d'excuse enregistré pour {$participant->full_name}.")
            ->success()
            ->send();
    }

    /**
     * Soumet définitivement le compte-rendu (verrouillage + e-mail admin).
     */
    public function submitAtelierReport(int $atelierId): void
    {
        if (! $this->activityPlanId) {
            return;
        }

        $atelier = RetreatAtelier::query()->findOrFail($atelierId);
        $user = Auth::user();
        $auth = app(RetreatAtelierAuthorizationService::class);

        if (! $auth->canManageAtelier($user, $atelier)) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Seuls le responsable ou l’adjoint peuvent soumettre le compte-rendu.')
                ->danger()
                ->send();

            return;
        }

        $existing = RetreatActivityAtelierReport::query()
            ->where('activity_plan_id', $this->activityPlanId)
            ->where('atelier_id', $atelierId)
            ->first();

        if ($existing?->isSubmitted()) {
            Notification::make()
                ->title('Compte-rendu verrouillé')
                ->body('Ce compte-rendu a déjà été soumis et ne peut plus être modifié.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->reportForms[$atelierId] ?? [];

        if (blank($data['sujet'] ?? null)) {
            Notification::make()
                ->title('Sujet requis')
                ->body('Renseignez le sujet avant de soumettre le compte-rendu.')
                ->warning()
                ->send();

            return;
        }

        $conducteurs = $this->buildConducteursPayload(
            (array) ($data['conducteur_user_ids'] ?? []),
            (array) ($data['conducteur_participant_ids'] ?? []),
            (array) ($data['conducteur_debat_keys'] ?? [])
        );

        $activityPlan = RetreatActivityPlan::query()
            ->with(['session.event'])
            ->findOrFail($this->activityPlanId);

        $report = RetreatActivityAtelierReport::query()->updateOrCreate(
            [
                'activity_plan_id' => $this->activityPlanId,
                'atelier_id' => $atelierId,
            ],
            [
                'sujet' => (string) $data['sujet'],
                'texte_biblique' => filled($data['texte_biblique'] ?? null) ? (string) $data['texte_biblique'] : null,
                'conducteurs' => $conducteurs,
                'resume' => filled($data['resume'] ?? null) ? (string) $data['resume'] : null,
                'recorded_by' => $user?->id,
                'submitted_at' => now(),
                'is_active' => true,
            ]
        );

        if ($user instanceof User) {
            try {
                app(RetreatActivityAtelierReportNotifier::class)->notifySubmitted(
                    $report->fresh(),
                    $activityPlan,
                    $atelier,
                    $user
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->reloadAtelierData();

        Notification::make()
            ->title('Compte-rendu soumis')
            ->body("Le compte-rendu de l’atelier {$atelier->numero} est verrouillé. Les administrateurs ont été notifiés par e-mail.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liste_pointages')
                ->label('Vue liste')
                ->icon('heroicon-o-table-cells')
                ->url(RetreatActivityAttendanceResource::getUrl('index')),
        ];
    }

    /**
     * Construit la liste des ateliers avec participants et pointages pour l'activité courante.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildAtelierBlocks(): array
    {
        if (! $this->activityPlanId) {
            return [];
        }

        $user = Auth::user();
        $auth = app(RetreatAtelierAuthorizationService::class);

        $atelierQuery = RetreatAtelier::query()
            ->where('is_active', true)
            ->with(['responsable', 'adjoint'])
            ->orderBy('numero');

        if (! $auth->isSuperAdmin($user)) {
            $managedIds = $auth->managedAtelierIds($user);
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
                ->where('activity_plan_id', $this->activityPlanId)
                ->whereIn('participant_id', $participants->pluck('id'))
                ->with('recorder')
                ->get()
                ->keyBy('participant_id');

            $report = RetreatActivityAtelierReport::query()
                ->where('activity_plan_id', $this->activityPlanId)
                ->where('atelier_id', $atelier->id)
                ->with('recorder')
                ->first();

            $blocks[] = [
                'atelier' => $atelier,
                'can_manage' => $auth->canManageAtelier($user, $atelier),
                'participants' => $participants,
                'attendances' => $attendances,
                'report' => $report,
                'debat_options' => $this->buildDebatOptions($atelier, $participants),
            ];
        }

        return $blocks;
    }

    /**
     * Prépare les formulaires de compte-rendu par atelier (évite le préfixe hydrate* réservé par Livewire).
     */
    protected function loadReportFormsForActivity(): void
    {
        $this->reportForms = [];

        foreach ($this->atelierBlocks as $block) {
            /** @var RetreatAtelier $atelier */
            $atelier = $block['atelier'];
            /** @var RetreatActivityAtelierReport|null $report */
            $report = $block['report'];

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

            $this->reportForms[$atelier->id] = [
                'sujet' => $report?->sujet ?? '',
                'texte_biblique' => $report?->texte_biblique ?? '',
                'conducteur_user_ids' => $userIds,
                'conducteur_participant_ids' => $participantIds,
                'conducteur_debat_keys' => $debatKeys,
                'resume' => $report?->resume ?? '',
            ];
        }
    }

    /**
     * Précharge les motifs d'excuse depuis les pointages existants.
     */
    protected function loadExcuseNotesFromAttendances(): void
    {
        foreach ($this->atelierBlocks as $block) {
            /** @var \Illuminate\Support\Collection<int, RetreatActivityAttendance> $attendances */
            $attendances = $block['attendances'];

            foreach ($attendances as $participantId => $attendance) {
                if ($attendance->status === 'excused' && filled($attendance->note)) {
                    $this->excuseNotes[(int) $participantId] = (string) $attendance->note;
                }
            }
        }
    }

    /**
     * Options du sélecteur « conducteur du débat » (participants + responsable + adjoint).
     *
     * @param  \Illuminate\Support\Collection<int, RetreatParticipant>  $participants
     * @return list<array{key: string, label: string}>
     */
    protected function buildDebatOptions(RetreatAtelier $atelier, $participants): array
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
     * @param  array<int, int|string>  $userIds
     * @param  array<int, int|string>  $participantIds
     * @param  array<int, string>  $debatKeys
     * @return list<array{type: string, id: int, label: string}>
     */
    protected function buildConducteursPayload(array $userIds, array $participantIds, array $debatKeys = []): array
    {
        $conducteurs = [];

        foreach (array_unique(array_filter(array_map('intval', $userIds))) as $userId) {
            $user = User::query()->find($userId);
            if ($user) {
                $conducteurs[] = [
                    'type' => 'user',
                    'id' => $user->id,
                    'label' => $user->name,
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
                $user = User::query()->find($userId);
                if ($user) {
                    $conducteurs[] = [
                        'type' => 'debat_user',
                        'id' => $user->id,
                        'label' => $user->name,
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

    protected function formatActivityLabel(RetreatActivityPlan $plan): string
    {
        $sessionDate = $plan->session?->start_at?->format('d/m/Y') ?? '—';
        $eventName = $plan->session?->event?->name ?? 'Retraite';

        return "{$plan->title} · {$eventName} · {$sessionDate}";
    }
}

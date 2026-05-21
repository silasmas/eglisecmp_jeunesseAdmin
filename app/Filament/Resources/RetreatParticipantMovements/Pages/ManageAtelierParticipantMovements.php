<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Pages;

use App\Filament\Resources\RetreatParticipantMovements\RetreatParticipantMovementResource;
use App\Models\ChurchEvent;
use App\Models\RetreatAtelier;
use App\Models\RetreatParticipant;
use App\Models\RetreatParticipantMovement;
use App\Models\User;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Enregistrement des mouvements (sortie/retour) regroupés par atelier.
 */
class ManageAtelierParticipantMovements extends Page
{
    protected static string $resource = RetreatParticipantMovementResource::class;

    protected static ?string $title = 'Mouvements par atelier';

    protected static ?string $navigationLabel = 'Mouvements par atelier';

    protected string $view = 'filament.resources.retreat-participant-movements.pages.manage-atelier-movements';

    public ?int $eventId = null;

    public bool $isLoadingAteliers = false;

    /** @var array<int, array<string, mixed>> */
    public array $atelierBlocks = [];

    /** @var array<int, string> Motif par participant (clé = participant_id). */
    public array $participantReasons = [];

    /** @var array<int, string> Observation par participant (clé = participant_id). */
    public array $participantNotes = [];

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('ViewAny:RetreatParticipantMovement')) {
            return true;
        }

        return app(RetreatAtelierAuthorizationService::class)->managesAnyAtelier($user);
    }

    /**
     * Charge les données si un événement est déjà sélectionné au montage.
     */
    public function mount(): void
    {
        if ($this->eventId) {
            $this->reloadAtelierData();
        }
    }

    /**
     * @return array<int, string>
     */
    public function getEventOptionsProperty(): array
    {
        return ChurchEvent::query()
            ->where('type', 'retraite')
            ->where('is_active', true)
            ->orderByDesc('start_at')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Recharge les blocs atelier lors du changement d'événement.
     */
    public function updatedEventId(): void
    {
        $this->isLoadingAteliers = true;
        $this->reloadAtelierData();
        $this->isLoadingAteliers = false;
    }

    /**
     * Charge les blocs atelier pour l'événement sélectionné.
     */
    public function reloadAtelierData(): void
    {
        $this->atelierBlocks = [];
        $this->participantReasons = [];
        $this->participantNotes = [];

        if (! $this->eventId) {
            return;
        }

        $this->atelierBlocks = $this->buildAtelierBlocks();
    }

    /**
     * Enregistre un mouvement pour un participant.
     */
    public function recordMovement(int $participantId, string $movementType): void
    {
        if (! $this->eventId) {
            return;
        }

        $allowed = ['exit', 'return'];
        if (! in_array($movementType, $allowed, true)) {
            return;
        }

        $participant = RetreatParticipant::query()->with('atelier')->findOrFail($participantId);
        $user = Auth::user();
        $auth = app(RetreatAtelierAuthorizationService::class);

        if (! $auth->canManageParticipant($user, $participant)) {
            Notification::make()
                ->title('Action non autorisée')
                ->body('Seuls le responsable ou l\'adjoint de l\'atelier peuvent enregistrer un mouvement.')
                ->danger()
                ->send();

            return;
        }

        $reason = trim($this->participantReasons[$participantId] ?? '');
        $note = trim($this->participantNotes[$participantId] ?? '');

        RetreatParticipantMovement::query()->create([
            'participant_id' => $participantId,
            'event_id' => $this->eventId,
            'movement_type' => $movementType,
            'moved_at' => now(),
            'authorized_by' => $user?->id,
            'reason' => filled($reason) ? $reason : null,
            'note' => filled($note) ? $note : null,
            'is_active' => true,
        ]);

        if ($movementType === 'exit') {
            $participant->update(['exit_allowed' => true]);
        }

        unset($this->participantReasons[$participantId], $this->participantNotes[$participantId]);

        $this->reloadAtelierData();

        $label = $movementType === 'exit' ? 'Sortie' : 'Retour';

        Notification::make()
            ->title('Mouvement enregistré')
            ->body("{$label} enregistrée pour {$participant->full_name}.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liste_mouvements')
                ->label('Vue liste')
                ->icon('heroicon-o-table-cells')
                ->url(RetreatParticipantMovementResource::getUrl('index')),
        ];
    }

    /**
     * Construit la liste des ateliers avec participants et mouvements pour l'événement courant.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildAtelierBlocks(): array
    {
        if (! $this->eventId) {
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
                ->where('event_id', $this->eventId)
                ->where('is_active', true)
                ->orderBy('prenom')
                ->orderBy('nom')
                ->get();

            if ($participants->isEmpty()) {
                continue;
            }

            $movements = RetreatParticipantMovement::query()
                ->where('event_id', $this->eventId)
                ->whereIn('participant_id', $participants->pluck('id'))
                ->where('is_active', true)
                ->with('authorizedBy')
                ->orderByDesc('moved_at')
                ->get()
                ->groupBy('participant_id');

            $blocks[] = [
                'atelier' => $atelier,
                'can_manage' => $auth->canManageAtelier($user, $atelier),
                'participants' => $participants,
                'movements' => $movements,
            ];
        }

        return $blocks;
    }
}

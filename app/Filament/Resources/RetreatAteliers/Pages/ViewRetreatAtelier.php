<?php

namespace App\Filament\Resources\RetreatAteliers\Pages;

use App\Filament\Resources\RetreatAteliers\RetreatAtelierResource;
use App\Filament\Support\ResendStaffAccessCredentialsFilamentAction;
use App\Filament\Support\RetreatAtelierAgeMismatchFilamentActions;
use App\Services\RetreatPlacementAssignmentService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail d'un atelier retraite avec actions de correction d'âge.
 */
class ViewRetreatAtelier extends ViewRecord
{
    protected static string $resource = RetreatAtelierResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ResendStaffAccessCredentialsFilamentAction::make(),
            RetreatAtelierAgeMismatchFilamentActions::reassignIntelligentlyAction(),
            RetreatAtelierAgeMismatchFilamentActions::quarantineOnlyAction(),
            EditAction::make(),
        ];
    }

    /**
     * Données supplémentaires pour la vue (compteur hors tranche).
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['age_mismatch_count'] = app(RetreatPlacementAssignmentService::class)
            ->countMismatchedParticipantsForAtelier($this->getRecord());

        return $data;
    }
}

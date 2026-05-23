<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use Illuminate\Contracts\View\View;

class RetreatInscriptionJustificatifController extends Controller
{
    public function __invoke(string $token): View
    {
        $participant = RetreatParticipant::query()
            ->with(['event', 'payments.event', 'chambre', 'atelier'])
            ->where('download_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return view('retraite-inscription.justificatif', [
            'participant' => $participant,
            'showPlacements' => $this->shouldShowPlacements($participant),
        ]);
    }

    /**
     * @param RetreatParticipant $participant Participant
     * @return bool Afficher chambre et atelier
     */
    private function shouldShowPlacements(RetreatParticipant $participant): bool
    {
        $startAt = $participant->event?->start_at;

        if (! $startAt) {
            return false;
        }

        return now()->gte($startAt->copy()->startOfDay());
    }
}

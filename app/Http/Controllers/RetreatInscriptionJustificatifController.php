<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Support\RetreatPlacementVisibility;
use App\Support\RetreatPublicPortalGate;
use Illuminate\Contracts\View\View;

class RetreatInscriptionJustificatifController extends Controller
{
    /**
     * @param  string  $token Token participant
     * @return View
     */
    public function __invoke(string $token): View
    {
        $participant = RetreatParticipant::query()
            ->with(['event', 'payments.event', 'chambre', 'atelier'])
            ->where('download_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        if (RetreatPublicPortalGate::isEventPubliclyClosed($participant->event)) {
            return RetreatPublicPortalGate::participantEventClosedView($participant);
        }

        $showPlacements = RetreatPlacementVisibility::shouldReveal($participant);

        return view('retraite-inscription.justificatif', [
            'participant' => $participant,
            'showPlacements' => $showPlacements,
            'placementsPendingMessage' => $showPlacements ? null : RetreatPlacementVisibility::pendingMessage($participant),
        ]);
    }
}

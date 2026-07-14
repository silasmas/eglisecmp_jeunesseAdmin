<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Support\ChurchEventParticipantDocuments;
use App\Support\RetreatBilletPresentation;
use App\Support\RetreatPlacementVisibility;
use App\Support\RetreatPublicPortalGate;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Billet participant réservé aux inscriptions payées et validées.
 */
class RetreatInscriptionBilletController extends Controller
{
    /**
     * @param string $token Token de téléchargement participant
     * @return View
     */
    public function __invoke(string $token): View
    {
        $participant = $this->resolveParticipant($token);

        if (RetreatPublicPortalGate::isEventPubliclyClosed($participant->event)) {
            return RetreatPublicPortalGate::participantEventClosedView($participant);
        }

        if (! $participant->paiement_valide) {
            throw new AccessDeniedHttpException('Le billet est disponible uniquement après validation du paiement.');
        }

        $payment = $participant->payments->sortByDesc('id')->first();
        $showPlacements = RetreatPlacementVisibility::shouldReveal($participant);
        $paymentReference = $payment?->reference;

        return view('retraite-inscription.billet', [
            'participant' => $participant,
            'payment' => $payment,
            'accessUrl' => route('retraite.inscription.acces', ['token' => $participant->download_token], absolute: true),
            'showPlacements' => $showPlacements,
            'placementsPendingMessage' => $showPlacements ? null : RetreatPlacementVisibility::pendingMessage($participant),
            'participantDocuments' => ChurchEventParticipantDocuments::entries($participant->event),
            'ticketName' => RetreatBilletPresentation::displayName($participant),
            'ticketStatus' => RetreatBilletPresentation::statusLabel($participant),
            'ticketHebergement' => RetreatBilletPresentation::hebergementLabel($participant),
            'ticketCode' => RetreatBilletPresentation::ticketCode($participant, $paymentReference),
            'rulesDocument' => config('retraite_billet_documents.rules'),
            'itemsDocument' => config('retraite_billet_documents.items'),
        ]);
    }

    /**
     * @param string $token Token participant
     * @return RetreatParticipant
     */
    private function resolveParticipant(string $token): RetreatParticipant
    {
        return RetreatParticipant::query()
            ->with(['event', 'payments.event', 'chambre', 'atelier'])
            ->where('download_token', $token)
            ->where('is_active', true)
            ->firstOrFail();
    }
}

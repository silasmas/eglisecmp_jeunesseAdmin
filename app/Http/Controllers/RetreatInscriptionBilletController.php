<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Support\RetreatBilletPageBuilder;
use App\Support\RetreatPublicPortalGate;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        return RetreatBilletPageBuilder::render($participant);
    }

    /**
     * @param string $token Token participant
     * @return RetreatParticipant
     */
    private function resolveParticipant(string $token): RetreatParticipant
    {
        $participant = RetreatParticipant::query()
            ->with(['event', 'payments.event', 'chambre', 'atelier'])
            ->where('download_token', $token)
            ->where('is_active', true)
            ->first();

        if ($participant === null) {
            $participant = RetreatParticipant::query()
                ->with(['event', 'payments.event', 'chambre', 'atelier'])
                ->where('download_token', $token)
                ->first();
        }

        if ($participant === null) {
            throw new NotFoundHttpException(
                'Billet introuvable : ce lien ne correspond à aucune inscription sur ce serveur. '
                .'Utilisez le lien reçu par e-mail après validation du paiement, ou contactez l\'administration.'
            );
        }

        return $participant;
    }
}

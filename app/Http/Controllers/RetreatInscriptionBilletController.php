<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
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

        if (! $participant->paiement_valide) {
            throw new AccessDeniedHttpException('Le billet est disponible uniquement après validation du paiement.');
        }

        $payment = $participant->payments->sortByDesc('id')->first();

        return view('retraite-inscription.billet', [
            'participant' => $participant,
            'payment' => $payment,
            'accessUrl' => route('retraite.inscription.acces', ['token' => $participant->download_token], absolute: true),
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

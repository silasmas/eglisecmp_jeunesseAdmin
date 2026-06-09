<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Support\RetreatVerifierSession;
use Illuminate\Contracts\View\View;

/**
 * Page publique de contrôle d’accès affichée lors du scan du QR du billet.
 */
class RetreatInscriptionAccessController extends Controller
{
    /**
     * @param string $token Token de téléchargement participant
     * @return View
     */
    public function __invoke(string $token): View
    {
        $participant = RetreatParticipant::query()
            ->with(['event', 'payments.event', 'chambre', 'atelier'])
            ->where('download_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $payment = $participant->payments->sortByDesc('id')->first();
        $accessGranted = (bool) $participant->paiement_valide
            && in_array($participant->registration_status, ['confirmed', 'valide'], true);

        return view('retraite-inscription.acces', [
            'participant' => $participant,
            'payment' => $payment,
            'accessGranted' => $accessGranted,
            'showPlacements' => RetreatVerifierSession::currentUser() !== null,
        ]);
    }
}

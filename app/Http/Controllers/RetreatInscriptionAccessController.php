<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
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

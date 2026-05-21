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
        ]);
    }
}

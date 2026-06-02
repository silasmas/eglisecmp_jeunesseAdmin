<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Affiche le studio badges (interface React) pour les super administrateurs.
 */
class RetreatBadgeStudioController extends Controller
{
    /**
     * @return View Vue principale du studio badges.
     */
    public function __invoke(): View
    {
        return view('studio-badge.index', [
            'templateUrl' => asset('assets/studio-badge/badge-participant.png'),
            'portalUrl' => url('/'),
            'adminUrl' => url('/admin'),
            'logoutUrl' => route('studio-badge.logout'),
        ]);
    }
}

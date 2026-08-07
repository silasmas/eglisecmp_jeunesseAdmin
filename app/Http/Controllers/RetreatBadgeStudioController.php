<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use Illuminate\View\View;

/**
 * Affiche le studio badges (interface React) pour les utilisateurs autorisés.
 */
class RetreatBadgeStudioController extends Controller
{
    /**
     * @return View Vue principale du studio badges.
     */
    public function __invoke(): View
    {
        $user = request()->user();
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        return view('studio-badge.index', [
            'templateUrl' => asset('assets/studio-badge/composants/fond-badge.png'),
            'assetBaseUrl' => asset('assets/studio-badge'),
            'portalUrl' => url('/'),
            'adminUrl' => url('/admin'),
            'logoutUrl' => route('studio-badge.logout'),
            'sessionUserName' => (string) ($user?->name ?? $user?->email ?? ''),
            'sessionEventName' => $event?->name,
            'sessionApiUrl' => route('studio-badge.api.session'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use Illuminate\View\View;

/**
 * Affiche les studios badges (classique ou HD badgecmp) pour les utilisateurs autorisés.
 */
class RetreatBadgeStudioController extends Controller
{
    /**
     * Studio classique (v1) — export html2canvas, modèle badge-participant.png.
     *
     * @return View
     */
    public function classic(): View
    {
        return $this->renderStudio('studio-badge.index', [
            'studioVariant' => 'classic',
            'templateUrl' => asset('assets/studio-badge/badge-participant.png'),
            'viteEntry' => ['resources/css/studio-badge.css', 'resources/js/studio-badge/main.tsx'],
        ]);
    }

    /**
     * Studio HD (v2) — moteur canvas badgecmp + composants fond/nom/atelier/chambre.
     *
     * @return View
     */
    public function hd(): View
    {
        return $this->renderStudio('studio-badge.hd', [
            'studioVariant' => 'hd',
            'templateUrl' => asset('assets/studio-badge/composants/fond-badge.png'),
            'assetBaseUrl' => asset('assets/studio-badge'),
            'viteEntry' => ['resources/css/studio-badge.css', 'resources/js/studio-badge-hd/main.tsx'],
        ]);
    }

    /**
     * Prépare les données communes des vues studio.
     *
     * @param  string  $view  Nom de vue Blade
     * @param  array<string, mixed>  $extra  Données spécifiques à la variante
     * @return View
     */
    private function renderStudio(string $view, array $extra): View
    {
        $user = request()->user();
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        return view($view, array_merge([
            'portalUrl' => url('/'),
            'adminUrl' => url('/admin'),
            'logoutUrl' => route('studio-badge.logout'),
            'classicUrl' => route('studio-badge.index'),
            'hdUrl' => route('studio-badge.hd'),
            'sessionUserName' => (string) ($user?->name ?? $user?->email ?? ''),
            'sessionEventName' => $event?->name,
            'sessionApiUrl' => route('studio-badge.api.session'),
            'apiParticipantsUrl' => route('studio-badge.api.participants'),
        ], $extra));
    }
}

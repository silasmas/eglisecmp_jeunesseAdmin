<?php

namespace App\Filament\Widgets;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Support\RetreatActiveEventScope;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Accès rapide au studio badges depuis le tableau de bord Filament.
 * Visible uniquement si la session utilisateur dispose de View:BadgeStudio.
 */
class RetreatBadgeStudioWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.retreat-badge-studio-widget';

    /**
     * Affiche le widget seulement pour les utilisateurs autorisés et actifs.
     *
     * @return bool
     */
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && (bool) $user->is_active
            && $user->can('View:BadgeStudio');
    }

    /**
     * Données passées à la vue Blade du widget.
     *
     * @return array{
     *   studioUrl: string,
     *   eventName: string|null,
     *   participantsCount: int,
     *   userName: string
     * }
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        $participantsQuery = RetreatActiveEventScope::applyToParticipants(
            RetreatParticipant::query()->where('is_active', true)
        );

        if ($event !== null) {
            $participantsQuery->where('event_id', $event->getKey());
        }

        return [
            'studioUrl' => route('studio-badge.index'),
            'eventName' => $event?->name,
            'participantsCount' => (int) $participantsQuery->count(),
            'userName' => (string) ($user?->name ?? $user?->email ?? 'Utilisateur'),
        ];
    }
}

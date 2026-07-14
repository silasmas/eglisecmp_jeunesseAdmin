<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatBilletPageBuilder;
use Filament\Actions\Action;

/**
 * Action Filament : ouvrir la prévisualisation admin du billet participant.
 */
final class RetreatBilletPreviewFilamentAction
{
    /**
     * @param string $name Identifiant de l'action Filament
     * @return Action Action configurée pour ouvrir le billet dans un nouvel onglet
     */
    public static function make(string $name = 'preview_billet'): Action
    {
        return Action::make($name)
            ->label('Prévisualiser le billet')
            ->icon('heroicon-o-ticket')
            ->color('primary')
            ->url(fn ($record): ?string => RetreatBilletPageBuilder::adminPreviewUrl(self::resolveParticipant($record)))
            ->openUrlInNewTab()
            ->visible(fn ($record): bool => self::canPreview($record));
    }

    /**
     * @param mixed $record Enregistrement table / infolist / page
     * @return bool True si la prévisualisation billet est possible
     */
    public static function canPreview(mixed $record): bool
    {
        $participant = self::resolveParticipant($record);

        return $participant !== null && filled($participant->download_token);
    }

    /**
     * @param mixed $record Enregistrement table / infolist / page
     * @return RetreatParticipant|null Participant concerné
     */
    public static function resolveParticipant(mixed $record): ?RetreatParticipant
    {
        if ($record instanceof RetreatParticipant) {
            return $record;
        }

        if ($record instanceof RetreatPayment) {
            return $record->participant;
        }

        return null;
    }
}

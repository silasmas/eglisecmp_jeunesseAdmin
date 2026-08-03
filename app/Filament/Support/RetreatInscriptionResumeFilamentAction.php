<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatInscriptionResumeUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

/**
 * Action Filament : afficher et copier le lien de reprise d'inscription (étape paiement).
 */
class RetreatInscriptionResumeFilamentAction
{
    /**
     * @param string $name Identifiant Filament de l'action
     * @return Action
     */
    public static function make(string $name = 'resumeInscriptionLink'): Action
    {
        return Action::make($name)
            ->label('Lien reprise paiement')
            ->icon('heroicon-o-link')
            ->color('primary')
            ->visible(fn (RetreatParticipant|RetreatPayment $record): bool => self::isVisible($record))
            ->modalHeading('Lien pour reprendre l’inscription')
            ->modalDescription(fn (RetreatParticipant|RetreatPayment $record): string => sprintf(
                'Envoyez ce lien à %s (SMS, WhatsApp, e-mail…) pour qu’il ou elle reprenne directement à l’étape paiement.',
                self::recipientLabel($record),
            ))
            ->form(fn (RetreatParticipant|RetreatPayment $record): array => [
                TextInput::make('resume_url')
                    ->label('Lien à partager')
                    ->default(fn (): ?string => self::resolveUrl($record))
                    ->readOnly()
                    ->copyable(copyMessage: 'Lien copié')
                    ->columnSpanFull(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->extraModalFooterActions(fn (RetreatParticipant|RetreatPayment $record): array => [
                Action::make('openResumeLink')
                    ->label('Ouvrir le lien')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): ?string => self::resolveUrl($record))
                    ->openUrlInNewTab(),
            ]);
    }

    /**
     * @param RetreatParticipant|RetreatPayment $record Enregistrement Filament
     * @return bool
     */
    protected static function isVisible(RetreatParticipant|RetreatPayment $record): bool
    {
        if ($record instanceof RetreatParticipant) {
            return RetreatInscriptionResumeUrl::canResumeForParticipant($record);
        }

        return RetreatInscriptionResumeUrl::canResumeForPayment($record);
    }

    /**
     * @param RetreatParticipant|RetreatPayment $record Enregistrement Filament
     * @return string|null
     */
    protected static function resolveUrl(RetreatParticipant|RetreatPayment $record): ?string
    {
        if ($record instanceof RetreatParticipant) {
            return RetreatInscriptionResumeUrl::urlForParticipant($record);
        }

        return RetreatInscriptionResumeUrl::urlForPayment($record);
    }

    /**
     * @param RetreatParticipant|RetreatPayment $record Enregistrement Filament
     * @return string
     */
    protected static function recipientLabel(RetreatParticipant|RetreatPayment $record): string
    {
        if ($record instanceof RetreatParticipant) {
            return $record->full_name ?: 'le participant';
        }

        return $record->participant?->full_name ?: 'le participant';
    }
}

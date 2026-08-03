<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatInscriptionResumeUrl;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Action Filament : afficher et copier le lien de reprise d'inscription (étape paiement).
 */
class RetreatInscriptionResumeFilamentAction
{
    /**
     * Modale standard pour un paiement ou un participant retraite.
     *
     * @param string $name Identifiant Filament de l'action
     * @return Action
     */
    public static function make(string $name = 'resumeInscriptionLink'): Action
    {
        return self::makeCopyModal(
            name: $name,
            label: 'Lien reprise paiement',
            resolveUrl: fn (RetreatParticipant|RetreatPayment $record): ?string => self::resolveUrl($record),
            visible: fn (RetreatParticipant|RetreatPayment $record): bool => self::isVisible($record),
            modalDescription: fn (RetreatParticipant|RetreatPayment $record): string => sprintf(
                'Envoyez ce lien complet à %s (SMS, WhatsApp, e-mail…) pour qu’il ou elle reprenne directement à l’étape paiement.',
                self::recipientLabel($record),
            ),
        );
    }

    /**
     * Modale réutilisable : affiche l’URL complète et la copie dans le presse-papiers.
     *
     * @param string $name Identifiant Filament
     * @param string $label Libellé du bouton
     * @param Closure $resolveUrl fn (mixed $record): ?string URL absolue
     * @param Closure $visible fn (mixed $record): bool
     * @param Closure|null $modalDescription Texte d’aide sous le titre
     * @return Action
     */
    public static function makeCopyModal(
        string $name,
        string $label,
        Closure $resolveUrl,
        Closure $visible,
        ?Closure $modalDescription = null,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-link')
            ->color('primary')
            ->visible($visible)
            ->modalHeading('Lien pour reprendre l’inscription')
            ->modalDescription($modalDescription ?? 'Copiez l’URL complète ci-dessous et envoyez-la au participant.')
            ->fillForm(fn (mixed $record): array => [
                'resume_url' => ($resolveUrl)($record) ?? '',
            ])
            ->form([
                Textarea::make('resume_url')
                    ->label('Lien à partager (URL complète)')
                    ->rows(3)
                    ->readOnly()
                    ->extraInputAttributes([
                        'onclick' => 'this.select()',
                        'style' => 'font-family: monospace; font-size: 0.85rem;',
                    ])
                    ->columnSpanFull(),
            ])
            ->modalSubmitActionLabel('Copier le lien')
            ->modalCancelActionLabel('Fermer')
            ->action(function (array $data, Component $livewire): void {
                self::copyUrlToClipboard($data, $livewire);
            })
            ->extraModalFooterActions(function (mixed $record) use ($resolveUrl): array {
                $url = ($resolveUrl)($record);

                if (! filled($url)) {
                    return [];
                }

                return [
                    Action::make('openResumeLink')
                        ->label('Ouvrir le lien')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url($url)
                        ->openUrlInNewTab(),
                ];
            });
    }

    /**
     * Copie l’URL dans le presse-papiers et notifie l’admin.
     *
     * @param array<string, mixed> $data Données du formulaire modal
     * @param Component $livewire Composant Livewire Filament
     * @return void
     */
    public static function copyUrlToClipboard(array $data, Component $livewire): void
    {
        $url = trim((string) ($data['resume_url'] ?? ''));

        if ($url === '' || ! str_starts_with($url, 'http')) {
            Notification::make()
                ->title('Lien indisponible')
                ->body('Impossible de générer une URL de reprise valide pour ce dossier.')
                ->danger()
                ->send();

            return;
        }

        $livewire->js('navigator.clipboard.writeText('.json_encode($url).')');

        Notification::make()
            ->title('Lien copié')
            ->body('Collez-le dans un SMS, WhatsApp ou un e-mail.')
            ->success()
            ->send();
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

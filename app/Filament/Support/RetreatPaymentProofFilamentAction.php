<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Support\RetreatParticipantPaymentProof;
use App\Support\RetreatPaymentProofUrl;
use Filament\Actions\Action;
use Illuminate\Support\Str;

/**
 * Action Filament : afficher la preuve de paiement participant dans une modale.
 */
final class RetreatPaymentProofFilamentAction
{
    /**
     * @param string $name Identifiant de l'action Filament
     * @return Action Action configurée avec modale de prévisualisation
     */
    public static function make(string $name = 'voir_preuve_paiement'): Action
    {
        return Action::make($name)
            ->label('Preuve')
            ->icon('heroicon-o-document-magnifying-glass')
            ->modalHeading('Preuve de paiement')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalWidth('5xl')
            ->modalContent(fn ($record) => view('filament.partials.payment-proof-modal', [
                'participant' => self::resolveParticipant($record),
                'proofUrl' => self::proofUrl($record),
                'mediaKind' => self::mediaKind($record),
            ]))
            ->visible(fn ($record): bool => self::hasViewableProof($record));
    }

    /**
     * @param mixed $record Enregistrement table / infolist
     * @return bool True si une preuve consultable existe
     */
    public static function hasViewableProof(mixed $record): bool
    {
        $participant = self::resolveParticipant($record);

        return RetreatParticipantPaymentProof::hasViewableProof($participant);
    }

    /**
     * @param mixed $record Enregistrement table / infolist
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

    /**
     * @param mixed $record Enregistrement table / infolist
     * @return string|null URL sécurisée de la preuve
     */
    public static function proofUrl(mixed $record): ?string
    {
        return RetreatPaymentProofUrl::forParticipant(self::resolveParticipant($record));
    }

    /**
     * @param mixed $record Enregistrement table / infolist
     * @return string pdf|image|file|unknown
     */
    public static function mediaKind(mixed $record): string
    {
        $participant = self::resolveParticipant($record);
        $path = (string) ($participant?->preuve_paiement ?? '');

        if (blank($path)) {
            return 'unknown';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return Str::endsWith(strtolower($path), '.pdf') ? 'pdf' : 'file';
        }

        $lower = strtolower($path);

        if (Str::endsWith($lower, '.pdf')) {
            return 'pdf';
        }

        if (preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/', $lower) === 1) {
            return 'image';
        }

        return 'file';
    }
}

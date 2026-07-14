<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use App\Services\QrCode\QrCodeGeneratorService;

/**
 * QR code scannable du billet participant (lien public encodé).
 */
final class RetreatBilletQr
{
    private function __construct()
    {
    }

    /**
     * URL publique encodée dans le QR (page billet du participant).
     *
     * @param RetreatParticipant $participant Participant
     * @return string|null URL absolue ou null si token absent
     */
    public static function scanUrl(RetreatParticipant $participant): ?string
    {
        if (blank($participant->download_token)) {
            return null;
        }

        return RetreatMailUrl::route('retraite.inscription.billet', [
            'token' => $participant->download_token,
        ]);
    }

    /**
     * Image PNG du QR en data URI (génération serveur, lisible par tout scanner).
     *
     * @param RetreatParticipant $participant Participant
     * @return string|null Data URI image/png ou null
     */
    public static function imageDataUri(RetreatParticipant $participant): ?string
    {
        $scanUrl = self::scanUrl($participant);

        if ($scanUrl === null) {
            return null;
        }

        $pngBinary = app(QrCodeGeneratorService::class)->buildPngBinary($scanUrl, false);

        return 'data:image/png;base64,'.base64_encode($pngBinary);
    }
}

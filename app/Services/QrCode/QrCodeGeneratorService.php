<?php

namespace App\Services\QrCode;

use App\Models\GeneratedQrCode;
use App\Support\QrCodeLogoCatalog;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * Génère un PNG QR code pour un lien donné, avec logo Jeunesse ou CMP au centre.
 */
class QrCodeGeneratorService
{
    /**
     * Génère le PNG, le stocke sur le disque public et met à jour le modèle.
     *
     * @param GeneratedQrCode $qrCode Enregistrement cible
     * @return string Chemin relatif sur le disque public
     */
    public function generateAndStore(GeneratedQrCode $qrCode): string
    {
        $pngBinary = $this->buildPngBinary(
            (string) $qrCode->target_url,
            (bool) $qrCode->embed_logo,
            (string) ($qrCode->logo_key ?? QrCodeLogoCatalog::KEY_JEUNESSE)
        );

        $relativePath = 'qr-codes/qr-'.$qrCode->id.'.png';
        Storage::disk('public')->put($relativePath, $pngBinary);

        $qrCode->update(['file_path' => $relativePath]);

        return $relativePath;
    }

    /**
     * Construit le binaire PNG du QR code.
     *
     * @param string $targetUrl Lien encodé dans le QR
     * @param bool $embedLogo Afficher un logo au centre
     * @param string $logoKey Clé logo (jeunesse, cmp)
     * @return string Contenu binaire PNG
     */
    public function buildPngBinary(string $targetUrl, bool $embedLogo, string $logoKey = QrCodeLogoCatalog::KEY_JEUNESSE): string
    {
        $logoPath = $embedLogo ? QrCodeLogoCatalog::resolveAbsolutePath($logoKey) : null;
        $useLogo = $logoPath !== null;

        $builder = new Builder(
            writer: new PngWriter(),
            data: $targetUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 480,
            margin: 16,
            logoPath: $useLogo ? $logoPath : '',
            logoResizeToWidth: $useLogo ? 88 : null,
            logoPunchoutBackground: $useLogo,
        );

        return $builder->build()->getString();
    }
}

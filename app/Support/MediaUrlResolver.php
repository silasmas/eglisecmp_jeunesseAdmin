<?php

namespace App\Support;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidConversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Génère des URLs d’aperçu pour la médiathèque (S3 signé ou disque local).
 */
class MediaUrlResolver
{
    /**
     * @param HasMedia $owner Modèle Spatie (fichier médiathèque)
     * @param string $conversion Conversion demandée (thumb, preview, ou vide)
     * @param string|null $collection Collection média
     * @return string|null URL absolue utilisable dans &lt;img src&gt;
     */
    public static function resolve(HasMedia $owner, string $conversion = '', ?string $collection = null): ?string
    {
        $media = $owner->getFirstMedia($collection ?? 'default') ?? $owner->getFirstMedia();

        if (! $media) {
            return null;
        }

        $conversion = self::resolveAvailableConversion($media, $conversion);

        if (self::shouldUseTemporaryUrl($media)) {
            $minutes = (int) config('cmp.media_preview_signed_url_ttl_minutes', 360);

            try {
                return $media->getTemporaryUrl(now()->addMinutes($minutes), $conversion);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        try {
            $url = $media->getUrl($conversion);
        } catch (InvalidConversion $exception) {
            report($exception);
            $url = $media->getUrl();
        }

        if (is_string($url) && $url !== '' && ! str_starts_with($url, 'http')) {
            $base = config('filesystems.disks.'.$media->disk.'.url');

            if (is_string($base) && $base !== '') {
                return rtrim($base, '/').'/'.ltrim($url, '/');
            }
        }

        return $url;
    }

    /**
     * Retourne une conversion utilisable ou une chaîne vide (fichier original).
     *
     * @param Media $media Enregistrement média Spatie
     * @param string $conversion Conversion demandée
     * @return string
     */
    private static function resolveAvailableConversion(Media $media, string $conversion): string
    {
        if ($conversion === '') {
            return '';
        }

        $registered = $media->getMediaConversionNames();

        if (! in_array($conversion, $registered, true)) {
            return '';
        }

        if (! $media->hasGeneratedConversion($conversion)) {
            return '';
        }

        return $conversion;
    }

    /**
     * @param Media $media Enregistrement média Spatie
     * @return bool
     */
    private static function shouldUseTemporaryUrl(Media $media): bool
    {
        return config('filesystems.disks.'.$media->disk.'.driver') === 's3';
    }
}

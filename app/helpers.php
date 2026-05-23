<?php

use App\Support\MediaUrlResolver;
use Illuminate\Support\Facades\Storage;
use Slimani\MediaManager\Models\File;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

if (! function_exists('media_preview_url')) {
    /**
     * URL d’aperçu pour un fichier médiathèque (image dans la grille ou formulaire).
     *
     * @param File|HasMedia|null $file Fichier ou modèle média
     * @param string $conversion thumb, preview, ou vide pour l’original
     * @return string|null
     */
    function media_preview_url(File|HasMedia|null $file, string $conversion = ''): ?string
    {
        if ($file === null) {
            return null;
        }

        return MediaUrlResolver::resolve($file, $conversion);
    }
}

if (! function_exists('media_file_exists')) {
    /**
     * Vérifie que le binaire média existe sur le disque configuré.
     *
     * @param File|HasMedia|null $file Fichier médiathèque
     * @return bool
     */
    function media_file_exists(File|HasMedia|null $file): bool
    {
        if ($file === null) {
            return false;
        }

        $media = $file->getFirstMedia('default') ?? $file->getFirstMedia();

        if (! $media instanceof Media) {
            return false;
        }

        try {
            return Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}

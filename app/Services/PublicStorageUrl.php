<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Résout une URL publique pour un chemin stocké (local ou S3).
 */
class PublicStorageUrl
{
    /**
     * @param string|null $path Chemin relatif en base ou URL absolue
     * @return string|null URL utilisable par le navigateur
     */
    public function fromPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        return Storage::disk($this->disk())->url($path);
    }

    /**
     * @return string Disque pour la lecture des fichiers publics
     */
    private function disk(): string
    {
        return (string) config('cmp.upload_disk', config('filesystems.default'));
    }
}

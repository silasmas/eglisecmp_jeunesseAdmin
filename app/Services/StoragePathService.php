<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Noms de fichiers uniques et enregistrement sur le disque d'upload configuré.
 */
class StoragePathService
{
    /**
     * Disque utilisé pour les fichiers publics (upload + lecture).
     *
     * @return string Nom du disque Laravel
     */
    public function uploadDisk(): string
    {
        return (string) config('cmp.upload_disk', config('filesystems.default'));
    }

    /**
     * Génère un nom de fichier unique (UUID + extension).
     *
     * @param UploadedFile|TemporaryUploadedFile $file Fichier uploadé
     * @return string Nom de fichier seul (sans dossier)
     */
    public function uniqueFilename(UploadedFile|TemporaryUploadedFile $file): string
    {
        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->extension()
            ?: 'bin'
        );

        return Str::uuid()->toString().'.'.$extension;
    }

    /**
     * Chemin relatif complet (dossier + nom unique).
     *
     * @param string $folder Préfixe dossier (voir StoragePath)
     * @param UploadedFile|TemporaryUploadedFile $file Fichier uploadé
     * @return string Chemin relatif sur le disque
     */
    public function buildPath(string $folder, UploadedFile|TemporaryUploadedFile $file): string
    {
        return trim($folder, '/').'/'.$this->uniqueFilename($file);
    }

    /**
     * Enregistre un fichier uploadé et retourne le chemin relatif stocké en base.
     *
     * @param UploadedFile $file Fichier HTTP
     * @param string $folder Dossier cible
     * @return string Chemin relatif enregistré
     */
    public function storeUploadedFile(UploadedFile $file, string $folder): string
    {
        $relativePath = $this->buildPath($folder, $file);

        Storage::disk($this->uploadDisk())->putFileAs(
            trim($folder, '/'),
            $file,
            basename($relativePath)
        );

        return $relativePath;
    }
}

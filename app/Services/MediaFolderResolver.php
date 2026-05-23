<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Slimani\MediaManager\Models\Folder;

/**
 * Résout et met en cache les dossiers médiathèque (évite les requêtes RDS répétées à chaque upload).
 */
class MediaFolderResolver
{
    /**
     * @param string|null $directory Chemin type « events-affiches » ou « a/b/c »
     * @return int|null Identifiant du dossier feuille
     */
    public function resolveFolderId(?string $directory): ?int
    {
        if (blank($directory)) {
            return null;
        }

        $normalized = trim((string) $directory, '/');

        return Cache::remember(
            'media_folder_id:'.$normalized,
            now()->addDay(),
            fn (): ?int => $this->createFolderChain($normalized)
        );
    }

    /**
     * @param string $directory Chemin normalisé
     * @return int|null Dernier dossier créé
     */
    private function createFolderChain(string $directory): ?int
    {
        $segments = array_filter(explode('/', $directory));
        $parentId = null;

        foreach ($segments as $segment) {
            $folder = Folder::query()->firstOrCreate([
                'name' => $segment,
                'parent_id' => $parentId,
            ]);
            $parentId = $folder->id;
        }

        return $parentId;
    }
}

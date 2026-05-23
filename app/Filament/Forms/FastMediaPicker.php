<?php

namespace App\Filament\Forms;

use App\Models\Media\MediaFile;
use App\Services\MediaFolderResolver;
use App\Services\StoragePathService;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Slimani\MediaManager\Form\MediaPicker;
use Slimani\MediaManager\Models\File;

/**
 * MediaPicker optimisé : détecte les fichiers absents, permet remplacement et suppression.
 */
class FastMediaPicker extends MediaPicker
{
    /**
     * @return string Data-URI placeholder fichier absent
     */
    private static function missingFilePlaceholder(): string
    {
        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">'
            .'<rect width="120" height="120" fill="#f3ebef"/>'
            .'<text x="60" y="54" text-anchor="middle" font-family="sans-serif" font-size="11" fill="#851c46">Fichier</text>'
            .'<text x="60" y="72" text-anchor="middle" font-family="sans-serif" font-size="11" fill="#851c46">introuvable</text>'
            .'</svg>'
        );
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->nullable();
        $this->deletable();

        $this->saveUploadedFileUsing(static function (FastMediaPicker $component, TemporaryUploadedFile $file): ?string {
            $folderId = app(MediaFolderResolver::class)->resolveFolderId($component->getDirectory());
            $uniqueName = app(StoragePathService::class)->uniqueFilename($file);

            $fileModel = MediaFile::query()->create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'uploaded_by_user_id' => auth()->id(),
                'folder_id' => $folderId,
            ]);

            $media = $fileModel
                ->addMedia($file->getRealPath())
                ->usingFileName($uniqueName)
                ->toMediaCollection('default');

            $fileModel->update([
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'extension' => $media->extension,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
            ]);

            return (string) $fileModel->id;
        });

        $this->getUploadedFileUsing(static function (FastMediaPicker $component, string $file): ?array {
            $fileRecord = File::query()->find($file);

            if (! $fileRecord) {
                return null;
            }

            if (! media_file_exists($fileRecord)) {
                return [
                    'name' => ($fileRecord->name ?: 'Fichier').' — absent du stockage (remplacez ou supprimez)',
                    'size' => 1,
                    'type' => 'image/svg+xml',
                    'url' => self::missingFilePlaceholder(),
                ];
            }

            $conversion = $component->getConversion();
            $url = media_preview_url($fileRecord, $conversion !== '' ? $conversion : '');

            if (blank($url)) {
                return [
                    'name' => ($fileRecord->name ?: 'Fichier').' — URL indisponible (remplacez ou supprimez)',
                    'size' => 1,
                    'type' => 'image/svg+xml',
                    'url' => self::missingFilePlaceholder(),
                ];
            }

            return [
                'name' => $fileRecord->name,
                'size' => max(1, (int) ($fileRecord->size ?? 0)),
                'type' => $fileRecord->mime_type ?: 'image/jpeg',
                'url' => $url,
            ];
        });
    }
}

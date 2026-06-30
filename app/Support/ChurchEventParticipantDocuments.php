<?php

namespace App\Support;

use App\Models\ChurchEvent;
use App\Services\PublicStorageUrl;

/**
 * Documents téléchargeables depuis le billet participant (règlement, histoires).
 */
final class ChurchEventParticipantDocuments
{
    private function __construct()
    {
    }

    /**
     * Liste des documents publiés pour les participants d'un événement.
     *
     * @param  ChurchEvent|null  $event Événement retraite
     * @return array<int, array{key: string, label: string, url: string}>
     */
    public static function entries(?ChurchEvent $event): array
    {
        if ($event === null) {
            return [];
        }

        $urlResolver = app(PublicStorageUrl::class);
        $entries = [];

        if (filled($event->document_reglement)) {
            $url = $urlResolver->fromPath($event->document_reglement);
            if ($url !== null) {
                $entries[] = [
                    'key' => 'reglement',
                    'label' => 'Règlement intérieur',
                    'url' => $url,
                ];
            }
        }

        if (filled($event->document_histoires)) {
            $url = $urlResolver->fromPath($event->document_histoires);
            if ($url !== null) {
                $entries[] = [
                    'key' => 'histoires',
                    'label' => 'Histoires à apporter',
                    'url' => $url,
                ];
            }
        }

        return $entries;
    }

    /**
     * Indique si au moins un document est disponible pour l'événement.
     *
     * @param  ChurchEvent|null  $event Événement retraite
     * @return bool
     */
    public static function hasAny(?ChurchEvent $event): bool
    {
        return self::entries($event) !== [];
    }
}
